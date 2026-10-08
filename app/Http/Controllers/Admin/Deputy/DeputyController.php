<?php

namespace App\Http\Controllers\Admin\Deputy;

use Anthropic\Client as AnthropicClient;
use Anthropic\Core\Exceptions\NotFoundException;
use App\Http\Controllers\Controller;
use App\Models\AiAgent;
use App\Models\AiConversation;
use App\Models\AiModel;
use App\Services\Deputy\AgentRunner;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Deputy — AI agents. Which model each agent runs on (Claude or a downloaded local model),
 * every conversation the agents had with a transcript, and a place to test an agent.
 */
class DeputyController extends Controller
{
    public function dashboard(): View
    {
        $since = today()->subDays(29);
        $stats = fn ($q) => $q->where('started_at', '>=', $since)->where('channel', '!=', 'test');
        $byAgent = $stats(AiConversation::query())->selectRaw("ai_agent_id, count(*) as n, sum(input_tokens + output_tokens) as tokens, avg(duration_sec) as avg_sec,
                sum(case when outcome = 'resolved' then 1 else 0 end) as resolved, sum(case when outcome = 'handed_off' then 1 else 0 end) as handed")
            ->groupBy('ai_agent_id')->get()->keyBy('ai_agent_id');
        $byModel = $stats(AiConversation::query())->selectRaw('ai_model_id, count(*) as n, sum(input_tokens) as input, sum(output_tokens) as output')->groupBy('ai_model_id')->get()->keyBy('ai_model_id');
        $total = $byAgent->sum('n');

        return view('admin.deputy.dashboard', [
            'agents' => AiAgent::with(['model', 'fallback'])->orderByDesc('active')->orderBy('name')->get(),
            'models' => AiModel::withCount('agents')->orderBy('provider')->orderBy('name')->get(),
            'byAgent' => $byAgent,
            'byModel' => $byModel,
            'total' => $total,
            'resolvedRate' => $total ? round(100 * $byAgent->sum('resolved') / $total) : 0,
            'handed' => $byAgent->sum('handed'),
            'tokens' => $byAgent->sum('tokens'),
            'local' => AiModel::where('provider', 'local')->count(),
            'ready' => ['anthropic' => filled(config('admin.integrations.anthropic.env.ANTHROPIC_API_KEY')), 'local' => filled(config('admin.integrations.local_models.env.LOCAL_MODELS_URL'))],
            'recent' => AiConversation::with(['agent', 'model', 'customer'])->latest('started_at')->limit(8)->get(),
        ]);
    }

    // ---------- Conversations ----------

    public function conversations(Request $request): View
    {
        $f = $request->only(['start', 'end', 'account', 'agent', 'model', 'channel', 'outcome', 'topic']);
        $q = AiConversation::with(['agent', 'model', 'customer', 'handedTo'])
            ->when($f['start'] ?? null, fn ($q, $d) => $q->whereDate('started_at', '>=', $d))
            ->when($f['end'] ?? null, fn ($q, $d) => $q->whereDate('started_at', '<=', $d))
            ->when($f['account'] ?? null, fn ($q, $a) => $q->whereHas('customer', fn ($c) => $c->where('account', 'like', trim($a).'%')))
            ->when($f['agent'] ?? null, fn ($q, $a) => $q->where('ai_agent_id', $a))
            ->when($f['model'] ?? null, fn ($q, $m) => $q->where('ai_model_id', $m))
            ->when($f['channel'] ?? null, fn ($q, $c) => $q->where('channel', $c))
            ->when($f['outcome'] ?? null, fn ($q, $o) => $q->where('outcome', $o))
            ->when($f['topic'] ?? null, fn ($q, $t) => $q->where('topic', $t));

        return view('admin.deputy.conversations', [
            'f' => $f,
            'conversations' => $q->latest('started_at')->latest('id')->paginate(50)->withQueryString(),
            'agents' => AiAgent::orderBy('name')->get(['id', 'name']),
            'models' => AiModel::orderBy('name')->get(['id', 'name']),
            'topics' => AiConversation::distinct()->orderBy('topic')->pluck('topic'),
        ]);
    }

    public function conversation(AiConversation $conversation): View
    {
        return view('admin.deputy.conversation', ['c' => $conversation->load(['agent', 'model', 'customer', 'handedTo', 'user'])]);
    }

    // ---------- Agents ----------

    public function agents(): View
    {
        return view('admin.deputy.agents', ['agents' => AiAgent::with(['model', 'fallback'])->withCount('conversations')->withMax('conversations', 'started_at')->orderByDesc('active')->orderBy('name')->get()]);
    }

    public function agentForm(?AiAgent $agent = null): View
    {
        return view('admin.deputy.agent-form', [
            'agent' => $agent ?? new AiAgent(['effort' => 'medium', 'max_tokens' => 4000, 'active' => true, 'ai_model_id' => AiModel::where('model_id', config('deputy.default_model'))->value('id')]),
            'models' => AiModel::orderBy('provider')->orderBy('name')->get(),
        ]);
    }

    public function saveAgent(Request $request, ?AiAgent $agent = null): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'activity' => ['required', Rule::in(array_keys(config('deputy.activities')))],
            'description' => ['nullable', 'string', 'max:250'],
            'ai_model_id' => ['required', 'exists:ai_models,id'],
            'fallback_model_id' => ['nullable', 'different:ai_model_id', 'exists:ai_models,id'],
            'effort' => ['required', Rule::in(array_keys(config('deputy.efforts')))],
            'max_tokens' => ['required', 'integer', 'min:256', 'max:64000'],
            'system_prompt' => ['required', 'string', 'max:20000'],
            'active' => ['nullable', 'boolean'],
        ], ['fallback_model_id.different' => 'Pick a different model as the fallback.']);
        $data['active'] = (bool) ($data['active'] ?? false);
        $agent ? $agent->update($data) : AiAgent::create($data);

        return redirect()->route('deputy.agents')->with('status', 'Agent saved');
    }

    /** Test an agent: send it a message and see what its model answers. Saved as a Test conversation. */
    public function testForm(AiAgent $agent): View
    {
        return view('admin.deputy.agent-test', ['agent' => $agent->load(['model', 'fallback']),
            'tests' => $agent->conversations()->with('model', 'user')->where('channel', 'test')->latest('started_at')->limit(10)->get()]);
    }

    public function runTest(Request $request, AiAgent $agent, AgentRunner $runner): RedirectResponse
    {
        $data = $request->validate(['message' => ['required', 'string', 'max:4000']]);
        $r = $runner->run($agent->load(['model', 'fallback']), $data['message']);
        $conversation = AiConversation::create(['ai_agent_id' => $agent->id, 'ai_model_id' => $r['model']?->id, 'channel' => 'test', 'topic' => 'Test',
            'outcome' => $r['ok'] ? 'resolved' : 'failed', 'user_id' => $request->user()->id, 'started_at' => now(), 'duration_sec' => (int) ceil($r['ms'] / 1000),
            'input_tokens' => $r['input_tokens'], 'output_tokens' => $r['output_tokens'],
            'transcript' => 'Tester: '.str_replace("\n", ' ', $data['message'])."\n".($r['ok'] ? 'Agent: '.str_replace("\n", ' ', $r['text']) : 'System: No answer. '.$r['note'])]);

        return redirect()->route('deputy.agents.test', $agent)->with('agent_result', $r + ['conversation' => $conversation->id, 'model_name' => $r['model']?->name])->withInput();
    }

    // ---------- Models ----------

    public function models(): View
    {
        return view('admin.deputy.models', ['models' => AiModel::withCount(['agents', 'conversations'])->orderBy('provider')->orderBy('name')->get(),
            'usedAsFallback' => AiAgent::whereNotNull('fallback_model_id')->selectRaw('fallback_model_id, count(*) as n')->groupBy('fallback_model_id')->pluck('n', 'fallback_model_id')]);
    }

    public function modelForm(?AiModel $model = null): View
    {
        return view('admin.deputy.model-form', ['model' => $model ?? new AiModel(['provider' => 'local', 'status' => 'available'])]);
    }

    public function saveModel(Request $request, ?AiModel $model = null): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'provider' => ['required', Rule::in(array_keys(config('deputy.providers')))],
            'model_id' => ['required', 'string', 'max:120', 'regex:/^[A-Za-z0-9._:\/\-]+$/', Rule::unique('ai_models')->where('provider', $request->input('provider'))->ignore($model)],
            'source' => ['nullable', 'string', 'max:250'],
            'size_gb' => ['nullable', 'numeric', 'min:0', 'max:9999'],
            'quantization' => ['nullable', 'string', 'max:20'],
            'context_window' => ['nullable', 'integer', 'min:512', 'max:10000000'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], ['model_id.regex' => 'Use the model\'s id as the runtime knows it, like claude-opus-5-5 or llama3.1:8b.']);
        $model ? $model->update($data) : $model = AiModel::create($data + ['status' => $data['provider'] === 'local' ? 'missing' : 'available']);

        return redirect()->route('deputy.models')->with('status', $model->name.' saved. Use Check to confirm it is available.');
    }

    /** Is the model there? Claude: the Models API. Downloaded: the local runtime's list of models. */
    public function checkModel(AiModel $model): RedirectResponse
    {
        [$status, $message] = $model->isLocal() ? $this->checkLocal($model) : $this->checkClaude($model);
        $model->update(['status' => $status, 'checked_at' => now()]);

        return back()->with('status', $model->name.': '.$message);
    }

    private function checkClaude(AiModel $model): array
    {
        $key = (string) config('admin.integrations.anthropic.env.ANTHROPIC_API_KEY');
        if ($key === '') {
            return [$model->status, 'ANTHROPIC_API_KEY is not set, so it can\'t be checked (Sheriff → APIs → Anthropic → Configure).'];
        }
        try {
            $info = (new AnthropicClient(apiKey: $key))->models->retrieve($model->model_id);

            return ['available', 'available as '.$info->displayName.'.'];
        } catch (NotFoundException) {
            return ['missing', 'Anthropic doesn\'t offer a model with the id '.$model->model_id.'.'];
        } catch (\Throwable $e) {
            return [$model->status, 'could not check: '.mb_strimwidth($e->getMessage(), 0, 200, '…')];
        }
    }

    private function checkLocal(AiModel $model): array
    {
        $url = (string) config('admin.integrations.local_models.env.LOCAL_MODELS_URL');
        if ($url === '') {
            return [$model->status, 'LOCAL_MODELS_URL is not set, so it can\'t be checked (Sheriff → APIs → Local Models → Configure).'];
        }
        try {
            $names = collect(Http::timeout(10)->get(rtrim($url, '/').'/api/tags')->json('models', []))->pluck('name');
        } catch (ConnectionException) {
            return [$model->status, 'could not reach the local model server.'];
        }
        $found = $names->contains($model->model_id) || $names->contains($model->model_id.':latest');

        return $found ? ['available', 'downloaded and ready on the local server.'] : ['missing', 'not on the local server. Download it there (for Ollama: ollama pull '.$model->model_id.').'];
    }
}
