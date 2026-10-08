@extends('admin.layouts.app')

@section('content')
    @include('admin.partials.page-head', ['title' => 'AI Agents', 'sub' => 'Which model each agent runs on, and how the agents are doing over the last 30 days. Claude models run through the Anthropic API; downloaded models run on the local model server.',
        'actions' => '<a class="btn ghost" href="'.route('deputy.conversations').'">Conversations</a><a class="btn cyan" href="'.route('deputy.agents.create').'">New Agent</a>'])

    @foreach (['anthropic' => ['Anthropic', 'ANTHROPIC_API_KEY'], 'local' => ['Local Models', 'LOCAL_MODELS_URL']] as $k => [$name, $env])
        @unless ($ready[$k])<div class="banner-note" style="margin-bottom:12px"><b>{{ $name }}</b> isn't set up yet: agents on {{ $k === 'local' ? 'downloaded models' : 'Claude models' }} can't answer until <span class="mono">{{ $env }}</span> is uploaded in Sheriff → APIs → {{ $name }} → Configure.</div>@endunless
    @endforeach

    <div class="grid-4 stat-row">
        <a class="stat" href="{{ route('deputy.conversations') }}"><span>Conversations</span><strong>{{ number_format($total) }}</strong><small>last 30 days, tests not counted</small></a>
        <div class="stat"><span>Resolved by the Agent</span><strong>{{ $resolvedRate }}%</strong><small>{{ number_format($handed) }} handed to a person</small></div>
        <div class="stat"><span>Tokens Used</span><strong>{{ number_format($tokens) }}</strong><small>input and output</small></div>
        <a class="stat" href="{{ route('deputy.models') }}"><span>Models</span><strong>{{ $models->count() }}</strong><small>{{ $local }} downloaded, {{ $models->count() - $local }} Claude</small></a>
    </div>

    <div class="panel" style="margin-bottom:20px">
        <div class="panel-head"><h2>Model by Agent</h2><a class="btn sm ghost" href="{{ route('deputy.agents') }}">Agents</a></div>
        <div class="table-wrap"><table class="table">
            <thead><tr><th>Agent</th><th>Model</th><th>Fallback</th><th class="num">Conversations</th><th class="num">Resolved</th><th class="num">Avg Length</th><th class="num">Tokens</th></tr></thead>
            <tbody>@forelse ($agents as $a)
                @php $s = $byAgent[$a->id] ?? null; @endphp
                <tr @class(['muted' => ! $a->active])><td><a href="{{ route('deputy.agents.edit', $a) }}"><b>{{ $a->name }}</b></a><div class="help">{{ $a->activityLabel() }} · {{ config('deputy.efforts.'.$a->effort) }} effort{{ $a->active ? '' : ' · Paused' }} · <a href="{{ route('deputy.agents.test', $a) }}">Test</a></div></td>
                    <td>@include('admin.deputy._model', ['m' => $a->model])</td>
                    <td>@include('admin.deputy._model', ['m' => $a->fallback])</td>
                    <td class="num">@if ($s)<a href="{{ route('deputy.conversations', ['agent' => $a->id]) }}">{{ number_format($s->n) }}</a>@else 0 @endif</td>
                    <td class="num">{{ $s && $s->n ? round(100 * $s->resolved / $s->n).'%' : '—' }}</td>
                    <td class="num">{{ $s ? intdiv((int) $s->avg_sec, 60).':'.str_pad((string) ((int) $s->avg_sec % 60), 2, '0', STR_PAD_LEFT) : '—' }}</td>
                    <td class="num">{{ $s ? number_format($s->tokens) : '—' }}</td></tr>
            @empty <tr><td colspan="7" class="empty">No agents yet.</td></tr> @endforelse</tbody>
        </table></div>
    </div>

    <div class="grid-2">
        <div class="panel"><div class="panel-head"><h2>Models in Use</h2><a class="btn sm ghost" href="{{ route('deputy.models') }}">Models</a></div>
            <div class="table-wrap"><table>
                <thead><tr><th>Model</th><th class="num">Agents</th><th class="num">Conversations</th><th class="num">Tokens</th></tr></thead>
                <tbody>@foreach ($models as $m)
                    @php $u = $byModel[$m->id] ?? null; @endphp
                    <tr><td>@include('admin.deputy._model', ['m' => $m])<div class="help">{{ $m->providerLabel() }}@if ($m->status !== 'available') · {{ ucfirst($m->status) }}@endif</div></td>
                        <td class="num">{{ $m->agents_count }}</td><td class="num">{{ $u ? number_format($u->n) : 0 }}</td><td class="num">{{ $u ? number_format($u->input + $u->output) : 0 }}</td></tr>
                @endforeach</tbody>
            </table></div>
        </div>
        <div class="panel"><div class="panel-head"><h2>Latest Conversations</h2><a class="btn sm ghost" href="{{ route('deputy.conversations') }}">All</a></div>
            <div class="table-wrap"><table><tbody>@forelse ($recent as $c)
                <tr class="click" data-href="{{ route('deputy.conversations.show', $c) }}"><td class="nowrap"><a href="{{ route('deputy.conversations.show', $c) }}">{{ $c->started_at->format('n/j g:i A') }}</a><div class="help">{{ config('deputy.channels.'.$c->channel) }}</div></td>
                    <td>{{ $c->topic }}<div class="help">{{ $c->agent?->name }}</div></td><td>@include('admin.deputy._outcome', ['c' => $c])</td></tr>
            @empty <tr><td class="empty">No conversations yet.</td></tr> @endforelse</tbody></table></div>
        </div>
    </div>
@endsection
