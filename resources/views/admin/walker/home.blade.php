@extends('admin.layouts.app')

@section('head')
    {{-- While reports are running, refresh so their status updates --}}
    @if ($counts['running'] > 0)<meta http-equiv="refresh" content="15">@endif
@endsection

@section('content')
    @include('admin.partials.page-head', ['title' => 'Report Status', 'sub' => 'Every report run from Walker and Corral: running, completed, or ended with an error or did not finish.',
        'actions' => '<a class="btn cyan" href="'.route('walker.reports.index').'">Run a Report</a>'])

    <div class="grid-4 stat-row">
        @foreach (['running' => 'info', 'done' => 'ok', 'problem' => 'bad'] as $k => $tone)
            <a class="stat" href="{{ route('walker.home', ['status' => $k]) }}"><span>{{ \App\Http\Controllers\Admin\Walker\ReportController::TABS[$k] }}</span><strong>{{ number_format($counts[$k]) }}</strong></a>
        @endforeach
        <a class="stat" href="{{ route('walker.home', ['status' => 'all']) }}"><span>All Runs</span><strong>{{ number_format($counts['all']) }}</strong></a>
    </div>

    <div class="panel">
        <div class="panel-head"><nav class="tabs" aria-label="Status">
            @foreach (\App\Http\Controllers\Admin\Walker\ReportController::TABS as $k => $label)
                <a href="{{ route('walker.home', ['status' => $k]) }}" @class(['on' => $tab === $k]) @if ($tab === $k) aria-current="page" @endif>{{ $label }} <span class="count-pill">{{ $counts[$k] }}</span></a>
            @endforeach
        </nav></div>
        <div class="table-wrap"><table>
            <thead><tr><th class="num">Run</th><th>Report</th><th>Model</th><th>Requested By</th><th>From</th><th>Started</th><th class="num">Duration</th><th class="num">Rows</th><th>Status</th></tr></thead>
            <tbody>@forelse ($runs as $run)
                <tr class="click" data-href="{{ route('walker.runs.show', $run) }}">
                    <td class="num"><a href="{{ route('walker.runs.show', $run) }}">#{{ $run->id }}</a></td>
                    <td><b>{{ $run->title ?? \Illuminate\Support\Str::headline($run->report) }}</b></td>
                    <td class="mono">{{ $run->model ?? '—' }}</td>
                    <td>{{ $run->user?->name ?? 'System' }}</td>
                    <td>{{ ucfirst($run->app) }}</td>
                    <td>{{ ($run->started_at ?? $run->created_at)->format('n/j/Y g:i A') }}</td>
                    <td class="num">{{ $run->duration_ms !== null ? number_format($run->duration_ms / 1000, 1).'s' : '—' }}</td>
                    <td class="num">{{ $run->status === 'done' ? number_format($run->rows) : '—' }}</td>
                    <td>@include('admin.walker._state')</td></tr>
            @empty <tr><td colspan="9" class="empty">No runs here.</td></tr> @endforelse</tbody>
        </table></div>
        @include('admin.partials.pager', ['p' => $runs])
    </div>
@endsection
