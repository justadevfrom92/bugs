@extends('admin.layouts.app')

@section('content')
    @include('admin.partials.page-head', ['title' => 'Conversations', 'sub' => number_format($conversations->total()).' '.Str::plural('conversation', $conversations->total()).' handled by AI agents: calls, texts, chats, emails and tests, each with its transcript.'])

    <div class="search-layout">
        <aside class="search-side">
            <form method="get" class="panel"><div class="panel-head"><h2>Search Conversations</h2></div><div class="panel-body">
                <div class="field"><label for="start">Start</label><input id="start" name="start" type="date" value="{{ $f['start'] ?? '' }}"></div>
                <div class="field"><label for="end">End</label><input id="end" name="end" type="date" value="{{ $f['end'] ?? '' }}"></div>
                <div class="field"><label for="account">Account Number</label><input id="account" name="account" value="{{ $f['account'] ?? '' }}" inputmode="numeric"></div>
                <div class="field"><label for="agent">Agent</label><select id="agent" name="agent"><option value="">Any</option>@foreach ($agents as $a)<option value="{{ $a->id }}" @selected(($f['agent'] ?? '') == $a->id)>{{ $a->name }}</option>@endforeach</select></div>
                <div class="field"><label for="model">Model</label><select id="model" name="model"><option value="">Any</option>@foreach ($models as $m)<option value="{{ $m->id }}" @selected(($f['model'] ?? '') == $m->id)>{{ $m->name }}</option>@endforeach</select></div>
                <div class="field"><label for="channel">Channel</label><select id="channel" name="channel"><option value="">Any</option>@foreach (config('deputy.channels') as $v => $l)<option value="{{ $v }}" @selected(($f['channel'] ?? '') === $v)>{{ $l }}</option>@endforeach</select></div>
                <div class="field"><label for="topic">Issue</label><select id="topic" name="topic"><option value="">Any</option>@foreach ($topics as $t)<option @selected(($f['topic'] ?? '') === $t)>{{ $t }}</option>@endforeach</select></div>
                <div class="field"><label for="outcome">Outcome</label><select id="outcome" name="outcome"><option value="">Any</option>@foreach (config('deputy.outcomes') as $v => $l)<option value="{{ $v }}" @selected(($f['outcome'] ?? '') === $v)>{{ $l }}</option>@endforeach</select></div>
                <button class="btn">Search</button>@if (array_filter($f))<a class="btn ghost" href="{{ route('deputy.conversations') }}">Clear</a>@endif
            </div></form>
        </aside>
        <div class="search-main">
            <div class="panel"><div class="table-wrap"><table class="table">
                <thead><tr><th>Started</th><th>Account</th><th>Issue</th><th class="num">Length</th><th>Agent &amp; Model</th><th>Outcome</th><th>Transcript</th></tr></thead>
                <tbody>@forelse ($conversations as $c)
                    <tr class="click" data-href="{{ route('deputy.conversations.show', $c) }}">
                        <td class="nowrap">{{ $c->started_at->format('n/j/Y g:i A') }}<br><span class="help">{{ config('deputy.channels.'.$c->channel) }}</span></td>
                        <td>@if ($c->customer)<span class="mono">{{ $c->customer->account }}</span><br><span class="help">{{ $c->customer->name }}</span>@elseif ($c->user)<span class="help">Test by {{ $c->user->name }}</span>@else<span class="muted">—</span>@endif</td>
                        <td>{{ $c->topic }}</td>
                        <td class="num">{{ intdiv($c->duration_sec, 60) }}:{{ str_pad((string) ($c->duration_sec % 60), 2, '0', STR_PAD_LEFT) }}</td>
                        <td>{{ $c->agent?->name }}<br><span class="help">{{ $c->model?->name ?? 'No model answered' }}</span></td>
                        <td>@include('admin.deputy._outcome', ['c' => $c])@if ($c->handedTo)<br><span class="help">to {{ $c->handedTo->name }}</span>@endif</td>
                        <td><a href="{{ route('deputy.conversations.show', $c) }}">View</a></td></tr>
                @empty <tr><td colspan="7" class="empty">No conversations match.</td></tr> @endforelse</tbody>
            </table></div>
            @include('admin.partials.pager', ['p' => $conversations])</div>
        </div>
    </div>
@endsection
