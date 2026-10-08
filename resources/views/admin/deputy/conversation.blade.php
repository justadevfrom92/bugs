@extends('admin.layouts.app')

@section('crumb', 'Conversation '.$c->id)

@section('content')
    @include('admin.partials.page-head', ['title' => $c->topic.($c->customer ? ': '.$c->customer->name : ''),
        'sub' => $c->started_at->format('l, F j, Y g:i A').' · '.config('deputy.channels.'.$c->channel).' · '.intdiv($c->duration_sec, 60).' min '.($c->duration_sec % 60).' sec',
        'actions' => '<a class="btn ghost" href="'.route('deputy.conversations').'">Back to Conversations</a>'.($c->agent ? '<a class="btn ghost" href="'.route('deputy.agents.test', $c->agent).'">Test this Agent</a>' : '')])
    <div class="grid-2" style="align-items:start;grid-template-columns:minmax(0,1fr) minmax(0,2fr)">
        <div class="panel"><div class="panel-head"><h2>Conversation</h2>@include('admin.deputy._outcome', ['c' => $c])</div><div class="panel-body"><dl class="kv">
            <dt>Account</dt><dd>{{ $c->customer?->account ?? '—' }}@if ($c->customer)<br><span class="help">{{ $c->customer->name }}</span>@endif</dd>
            <dt>Agent</dt><dd>@if ($c->agent)<a href="{{ route('deputy.agents.edit', $c->agent) }}">{{ $c->agent->name }}</a><br><span class="help">{{ $c->agent->activityLabel() }}</span>@else — @endif</dd>
            <dt>Model</dt><dd>@include('admin.deputy._model', ['m' => $c->model])</dd>
            <dt>Issue</dt><dd>{{ $c->topic }}</dd>
            <dt>Handed to</dt><dd>{{ $c->handedTo?->name ?? '—' }}</dd>
            @if ($c->user)<dt>Tested by</dt><dd>{{ $c->user->name }}</dd>@endif
            <dt>Tokens</dt><dd>{{ number_format($c->input_tokens) }} in · {{ number_format($c->output_tokens) }} out</dd>
            <dt>Length</dt><dd>{{ intdiv($c->duration_sec, 60) }}:{{ str_pad((string) ($c->duration_sec % 60), 2, '0', STR_PAD_LEFT) }}</dd>
        </dl></div></div>
        <div class="panel"><div class="panel-head"><h2>Transcript</h2></div><div class="panel-body transcript">
            @forelse ($c->lines() as [$who, $said])
                <p @class(['agent' => in_array(strtolower($who), ['agent', 'system'], true)])><b>{{ $who }}</b> {{ $said }}</p>
            @empty <p class="muted">No transcript.</p> @endforelse
        </div></div>
    </div>
@endsection
