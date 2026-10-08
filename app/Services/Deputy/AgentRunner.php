<?php

namespace App\Services\Deputy;

use Anthropic\Client as AnthropicClient;
use Anthropic\Core\Exceptions\APIConnectionException;
use Anthropic\Core\Exceptions\APIStatusException;
use Anthropic\Core\Exceptions\AuthenticationException;
use Anthropic\Core\Exceptions\RateLimitException;
use App\Models\AiAgent;
use App\Models\AiModel;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Sends a message to an agent's model and returns the reply. Claude models go through
 * the Anthropic API; downloaded models go to the local runtime (Ollama's /api/chat).
 * If the agent's model fails, its fallback model is tried.
 */
class AgentRunner
{
    /**
     * @param  list<array{role: string, content: string}>  $history  earlier turns, oldest first
     * @return array{ok: bool, text: string, model: ?AiModel, input_tokens: int, output_tokens: int, ms: int, note: ?string}
     */
    public function run(AiAgent $agent, string $message, array $history = []): array
    {
        $messages = [...$history, ['role' => 'user', 'content' => $message]];
        $start = microtime(true);
        $errors = [];
        foreach (array_filter([$agent->model, $agent->fallback]) as $i => $model) {
            try {
                $reply = $model->isLocal() ? $this->local($agent, $model, $messages) : $this->claude($agent, $model, $messages);

                return $reply + ['ok' => true, 'model' => $model, 'ms' => self::ms($start), 'note' => $i ? 'Used the fallback model: '.implode(' ', $errors) : ($reply['note'] ?? null)];
            } catch (ModelUnavailable $e) {
                $errors[] = $model->name.': '.$e->getMessage();
            }
        }

        return ['ok' => false, 'text' => '', 'model' => null, 'input_tokens' => 0, 'output_tokens' => 0, 'ms' => self::ms($start), 'note' => implode(' ', $errors)];
    }

    private function claude(AiAgent $agent, AiModel $model, array $messages): array
    {
        $key = (string) config('admin.integrations.anthropic.env.ANTHROPIC_API_KEY');
        if ($key === '') {
            throw new ModelUnavailable('ANTHROPIC_API_KEY is not set (Sheriff → APIs → Anthropic → Configure).');
        }
        $client = new AnthropicClient(apiKey: $key);
        $params = ['maxTokens' => $agent->max_tokens, 'messages' => $messages, 'model' => $model->model_id, 'system' => $agent->system_prompt,
            'outputConfig' => ['effort' => $agent->effort]];
        try {
            // Current Claude models: if one declines a request for policy reasons, the API retries it on its default fallback model
            $reply = in_array($model->model_id, config('deputy.fallback_models'), true)
                ? $client->beta->messages->create(...$params, betas: ['server-side-fallback-2026-07-01'], fallbacks: 'default')
                : $client->messages->create(...$params);
        } catch (AuthenticationException) {
            throw new ModelUnavailable('Anthropic rejected the API key.');
        } catch (RateLimitException) {
            throw new ModelUnavailable('Anthropic rate limit reached; try again shortly.');
        } catch (APIStatusException $e) {
            throw new ModelUnavailable('Anthropic answered HTTP '.$e->status.'.');
        } catch (APIConnectionException) {
            throw new ModelUnavailable('Could not reach the Anthropic API.');
        }
        if ($reply->stopReason === 'refusal') {
            throw new ModelUnavailable('The model declined this request.');
        }
        $text = collect($reply->content)->filter(fn ($b) => $b->type === 'text')->map(fn ($b) => $b->text)->implode("\n");

        return ['text' => trim($text), 'input_tokens' => $reply->usage->inputTokens, 'output_tokens' => $reply->usage->outputTokens,
            'note' => $reply->stopReason === 'max_tokens' ? 'The reply hit the agent\'s max tokens and was cut off.' : null];
    }

    private function local(AiAgent $agent, AiModel $model, array $messages): array
    {
        $url = (string) config('admin.integrations.local_models.env.LOCAL_MODELS_URL');
        if ($url === '') {
            throw new ModelUnavailable('LOCAL_MODELS_URL is not set (Sheriff → APIs → Local Models → Configure).');
        }
        try {
            $r = Http::timeout(120)->acceptJson()->post(rtrim($url, '/').'/api/chat', [
                'model' => $model->model_id, 'stream' => false, 'options' => ['num_predict' => $agent->max_tokens],
                'messages' => [['role' => 'system', 'content' => $agent->system_prompt], ...$messages],
            ]);
        } catch (ConnectionException) {
            throw new ModelUnavailable('Could not reach the local model server.');
        }
        if (! $r->successful()) {
            throw new ModelUnavailable('The local model server answered HTTP '.$r->status().($r->json('error') ? ': '.$r->json('error') : '').'.');
        }

        return ['text' => trim((string) $r->json('message.content')), 'input_tokens' => (int) $r->json('prompt_eval_count'), 'output_tokens' => (int) $r->json('eval_count')];
    }

    private static function ms(float $start): int
    {
        return (int) round((microtime(true) - $start) * 1000);
    }
}
