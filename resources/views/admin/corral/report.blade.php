@extends('admin.layouts.app')

@section('content')
    @include('admin.partials.page-head', ['title' => 'Orders Report', 'sub' => 'Pull orders by date range and status. CSV downloads include every column.'])

    <form method="get" class="panel"><div class="panel-body" style="display:flex;flex-direction:column;gap:16px">
        <div class="form-row">
            <div class="field"><label for="start">Start</label><input id="start" name="start" type="date" value="{{ $f['start'] }}"></div>
            <div class="field"><label for="end">End</label><input id="end" name="end" type="date" value="{{ $f['end'] }}"></div>
            @include('admin.corral.reports._output')
        </div>
        <div class="actions">
            @foreach ($statuses as $s)
                <label class="check" style="margin-right:10px"><input type="checkbox" name="statuses[]" value="{{ $s }}" @checked(in_array($s, $f['statuses']))> {{ $s }}</label>
            @endforeach
        </div>
    </div></form>

    @if ($orders !== null)
        <div class="panel">
            @if ($f['output'] === 'summary')
                <div class="panel-head"><h2>Summary</h2><span class="muted">{{ $orders->count() }} orders</span></div>
                <div class="table-wrap"><table><thead><tr><th>Status</th><th class="num">Orders</th></tr></thead><tbody>
                    @forelse ($orders->countBy('status') as $status => $n)<tr><td>{{ $status }}</td><td class="num">{{ $n }}</td></tr>
                    @empty <tr><td colspan="2" class="empty">No orders in this range.</td></tr> @endforelse
                </tbody></table></div>
            @else
                <div class="panel-head"><h2>Orders</h2><span class="muted">{{ $orders->count() }} orders</span></div>
                @include('admin.corral.customers._rows', ['customers' => $orders, 'empty' => 'No orders in this range.'])
            @endif
        </div>
    @endif

    @include('admin.corral.reports._recent', ['route' => 'corral.reports.orders'])
@endsection
