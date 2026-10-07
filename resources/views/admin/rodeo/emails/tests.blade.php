@extends('admin.layouts.app')

@section('content')
    @include('admin.partials.page-head', ['title' => 'Test Sends', 'sub' => 'Every test of every template. Open a template’s Send Test page to send one or send one again.'])

    <div class="search-layout">
        <aside class="search-side">
            <form method="get" class="panel"><div class="panel-head"><h2>Search Tests</h2></div><div class="panel-body">
                <div class="field"><label for="start">Start</label><input id="start" name="start" type="date" value="{{ $f['start'] ?? '' }}"></div>
                <div class="field"><label for="end">End</label><input id="end" name="end" type="date" value="{{ $f['end'] ?? '' }}"></div>
                <div class="field"><label for="template">Template</label><select id="template" name="template"><option value="">Any</option>@foreach ($templates as $t)<option value="{{ $t->id }}" @selected(($f['template'] ?? '') == $t->id)>{{ $t->name }}</option>@endforeach</select></div>
                <div class="field"><label for="result">Result</label><select id="result" name="result"><option value="">Any</option>@foreach (['sent' => 'Sent', 'logged' => 'Logged only', 'failed' => 'Failed', 'partial' => 'Partly sent', 'suppressed' => 'Suppressed'] as $v => $l)<option value="{{ $v }}" @selected(($f['result'] ?? '') === $v)>{{ $l }}</option>@endforeach</select></div>
                <div class="field"><label for="user">Sent By</label><select id="user" name="user"><option value="">Anyone</option>@foreach ($users as $u)<option value="{{ $u->id }}" @selected(($f['user'] ?? '') == $u->id)>{{ $u->name }}</option>@endforeach</select></div>
                <button class="btn">Search</button>@if (array_filter($f))<a class="btn ghost" href="{{ route('rodeo.emails.tests') }}">Clear</a>@endif
            </div></form>
        </aside>
        <div class="search-main">
            <div class="panel"><div class="table-wrap"><table class="table">
                <thead><tr><th>When</th><th>Template</th><th>By</th><th>Sent To</th><th class="num">People</th><th>Result</th><th></th></tr></thead>
                <tbody>@forelse ($tests as $t)
                    <tr><td class="nowrap">{{ $t->created_at->format('n/j/Y g:i A') }}</td>
                        <td>@if ($t->template)<a href="{{ route('rodeo.templates.edit', $t->template) }}">{{ $t->template->name }}</a>@endif<div class="muted">{{ $t->subject }}</div></td>
                        <td>{{ $t->user?->name }}</td>
                        <td class="wrap">{{ \App\Http\Controllers\Admin\Rodeo\TemplateTestController::MODES[$t->mode] ?? $t->mode }}@if ($t->target)<div class="muted">{{ Str::limit($t->target, 60) }}</div>@endif</td>
                        <td class="num">{{ count($t->recipients) }}</td>
                        <td>@include('admin.partials.pill', ['text' => ['sent' => 'Sent', 'logged' => 'Logged only', 'failed' => 'Failed', 'partial' => 'Partly sent', 'suppressed' => 'Suppressed'][$t->status] ?? $t->status, 'tone' => ['sent' => 'ok', 'failed' => 'bad', 'partial' => 'warn', 'suppressed' => 'bad'][$t->status] ?? ''])</td>
                        <td class="actions">@if ($t->template)<a class="btn sm ghost" href="{{ route('rodeo.templates.test', $t->template) }}#history">Open</a>@endif</td></tr>
                @empty <tr><td colspan="7" class="empty">No tests match.</td></tr> @endforelse</tbody>
            </table></div>
            @include('admin.partials.pager', ['p' => $tests])</div>
        </div>
    </div>
@endsection
