<?php

namespace App\Services\Deputy;

use App\Models\AiAgent;
use App\Models\AiModel;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Sends a message to an agent's open-weight model and returns the reply. Downloaded models
 * go to the local Ollama server (/api/chat); hosted ones to the OpenAI-compatible endpoint
 * (/chat/completions). If the agent's model can't answer, its fallback model is tried.
 */
class AgentRunner
{
    /**
     * @param  list<array{role: string, content: string}>  $history  earlier turns, oldest first
     * @return array{ok: bool, text: string, model: ?AiModel, input_tokens: int, output_tokens: int, ms: int, note: ?string}
     */
    public function run(AiAgent $agent, string $message, array $history = []): array
    {
        $messages = [['role' => 'system', 'content' => $agent->system_prompt], ...$history, ['role' => 'user', 'content' => $message]];
        $start = microtime(true);
        $errors = [];
        foreach (array_values(array_filter([$agent->model, $agent->fallback])) as $i => $model) {
            try {
                $reply = $model->isLocal() ? $this->local($agent, $model, $messages) : $this->hosted($agent, $model, $messages);

                $note = trim(($i ? 'Used the fallback model. '.implode(' ', $errors) : '').' '.($reply['note'] ?? '')) ?: null;

                return array_merge($reply, ['ok' => true, 'model' => $model, 'ms' => self::ms($start), 'note' => $note]);
            } catch (ModelUnavailable $e) {
                $errors[] = $model->name.': '.$e->getMessage();
            }
        }

        return ['ok' => false, 'text' => '', 'model' => null, 'input_tokens' => 0, 'output_tokens' => 0, 'ms' => self::ms($start), 'note' => implode(' ', $errors)];
    }

    /** Ollama's chat API on your own server. */
    private function local(AiAgent $agent, AiModel $model, array $messages): array
    {
        $url = (string) config('admin.integrations.local_models.env.LOCAL_MODELS_URL');
        if ($url === '') {
            throw new ModelUnavailable('LOCAL_MODELS_URL is not set (Sheriff → APIs → Local Models → Configure).');
        }
        $r = $this->post(rtrim($url, '/').'/api/chat', null, [
            'model' => $model->model_id, 'stream' => false, 'messages' => $messages,
            'options' => ['temperature' => (float) $agent->temperature, 'num_predict' => $agent->max_tokens],
        ], 'the local model server');

        return ['text' => trim((string) $r->json('message.content')), 'input_tokens' => (int) $r->json('prompt_eval_count'), 'output_tokens' => (int) $r->json('eval_count'),
            'note' => $r->json('done_reason') === 'length' ? 'The reply hit the agent\'s max tokens and was cut off.' : null];
    }

    /** Any OpenAI-compatible endpoint serving open-weight models (vLLM, llama.cpp, LM Studio, OpenRouter, Together, Moonshot…). */
    private function hosted(AiAgent $agent, AiModel $model, array $messages): array
    {
        $url = (string) config('admin.integrations.open_models.env.OPEN_MODELS_URL');
        if ($url === '') {
            throw new ModelUnavailable('OPEN_MODELS_URL is not set (Sheriff → APIs → Open Models API → Configure).');
        }
        $r = $this->post(rtrim($url, '/').'/chat/completions', config('admin.integrations.open_models.env.OPEN_MODELS_API_KEY'), [
            'model' => $model->model_id, 'messages' => $messages, 'temperature' => (float) $agent->temperature, 'max_tokens' => $agent->max_tokens,
        ], 'the open models API');
        $choice = $r->json('choices.0');
        if (! $choice) {
            throw new ModelUnavailable('The open models API returned no answer.');
        }

        return ['text' => trim((string) ($choice['message']['content'] ?? '')), 'input_tokens' => (int) $r->json('usage.prompt_tokens'), 'output_tokens' => (int) $r->json('usage.completion_tokens'),
            'note' => ($choice['finish_reason'] ?? null) === 'length' ? 'The reply hit the agent\'s max tokens and was cut off.' : null];
    }

    private function post(string $url, ?string $key, array $body, string $what)
    {
        try {
            $r = Http::timeout(120)->acceptJson()->when(filled($key), fn ($h) => $h->withToken($key))->post($url, $body);
        } catch (ConnectionException) {
            throw new ModelUnavailable('Could not reach '.$what.'.');
        }
        if (in_array($r->status(), [401, 403], true)) {
            throw new ModelUnavailable(ucfirst($what).' rejected the API key.');
        }
        if (! $r->successful()) {
            $error = $r->json('error.message') ?? $r->json('error');
            throw new ModelUnavailable(ucfirst($what).' answered HTTP '.$r->status().(is_string($error) ? ': '.mb_strimwidth($error, 0, 200, '…') : '').'.');
        }

        return $r;
    }

    private static function ms(float $start): int
    {
        return (int) round((microtime(true) - $start) * 1000);
    }
}
