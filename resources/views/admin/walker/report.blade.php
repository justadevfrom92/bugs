@extends('admin.layouts.app')

@section('crumb', $def['title'])

@section('content')
    @include('admin.partials.page-head', ['title' => $def['title'], 'sub' => e($def['description']).' · Model <span class="mono">'.e($def['model']).'</span>'])

    <form method="post" action="{{ route('walker.reports.run', $def['key']) }}" class="panel">@csrf
        <div class="panel-head"><h2>Parameters</h2><button class="btn cyan">Run Report</button></div>
        <div class="panel-body form-row">
            @foreach ($filters as $name => $filter)
                @php [$label, $type] = $filter; $options = $filter[2] ?? []; $value = old($name, $defaults[$name] ?? null); @endphp
                @if ($type === 'multi')
                    <div class="field" style="flex-basis:100%"><label>{{ $label }}</label><div class="actions">
                        @foreach ($options as $v => $l)<label class="check" style="margin-right:10px"><input type="checkbox" name="{{ $name }}[]" value="{{ $v }}" @checked(in_array($v, (array) $value))> {{ $l }}</label>@endforeach
                    </div></div>
                @elseif ($type === 'select')
                    <div class="field"><label for="f-{{ $name }}">{{ $label }}</label><select id="f-{{ $name }}" name="{{ $name }}"><option value="">-- Any --</option>
                        @foreach ($options as $v => $l)<option value="{{ $v }}" @selected((string) $value === (string) $v)>{{ $l }}</option>@endforeach</select></div>
                @else
                    <div class="field"><label for="f-{{ $name }}">{{ $label }}</label><input id="f-{{ $name }}" name="{{ $name }}" type="{{ $type === 'date' ? 'date' : 'text' }}" value="{{ $value }}"></div>
                @endif
            @endforeach
        </div>
        <div class="panel-body help" style="padding-top:0">Columns: {{ implode(' · ', $columns) }}</div>
    </form>

    <div class="panel"><div class="panel-head"><h2>Recent Runs</h2></div><div class="table-wrap"><table>
        <thead><tr><th>Run</th><th>Requested</th><th>By</th><th class="num">Rows</th><th>Status</th></tr></thead>
        <tbody>@forelse ($runs as $run)
            <tr class="click" data-href="{{ route('walker.runs.show', $run) }}"><td><a href="{{ route('walker.runs.show', $run) }}">#{{ $run->id }}</a></td><td>{{ $run->created_at->format('n/j/Y g:i A') }}</td>
                <td>{{ $run->user?->name ?? 'System' }}</td><td class="num">{{ $run->status === 'done' ? number_format($run->rows) : '—' }}</td><td>@include('admin.walker._state')</td></tr>
        @empty <tr><td colspan="5" class="empty">Not run yet.</td></tr> @endforelse</tbody>
    </table></div></div>
@endsection
