@extends('admin.layouts.app')

@section('content')
    @include('admin.partials.page-head', ['title' => 'MSIDs & Promo Codes', 'sub' => 'Website links carry ?msid= and ?promo= (e.g. /checkout?msid=3001&promo=FALL25); orders keep them for reporting.'])
    <div class="grid-2" style="align-items:start">
        <div class="panel"><div class="panel-head"><h2>MSIDs (Sales Channels)</h2></div>
            <div class="table-wrap"><table><thead><tr><th>MSID</th><th>Channel</th><th>Type</th><th class="num">Orders</th><th>Last Order</th></tr></thead>
            <tbody>@foreach ($channels as $ch)<tr @class(['muted' => ! $ch->active])><td class="mono">{{ $ch->msid }}</td><td>{{ $ch->name }}</td><td>{{ $ch->type }}</td><td class="num">{{ $orders[$ch->msid]->n ?? 0 }}</td><td>{{ isset($orders[$ch->msid]) ? \Illuminate\Support\Carbon::parse($orders[$ch->msid]->last_at)->format('n/j/Y') : '—' }}</td></tr>@endforeach</tbody></table></div>
            <form method="post" action="{{ route('rodeo.channels.save') }}" class="panel-body form-row">@csrf
                <input name="msid" required placeholder="MSID" aria-label="MSID" style="width:10ch"><input name="name" required placeholder="Channel name" aria-label="Channel name" style="flex:1">
                <select name="type" aria-label="Type">@foreach (['Organic', 'Paid', 'Partner', 'Broker', 'Agent'] as $t)<option>{{ $t }}</option>@endforeach</select>
                <input type="hidden" name="active" value="1"><button class="btn sm">Add / Update</button>
            </form>
        </div>
        <div class="panel"><div class="panel-head"><h2>Promo Codes</h2></div>
            <div class="table-wrap"><table><thead><tr><th>Code</th><th>Description</th><th class="num">Credit</th><th>Dates</th><th class="num">Uses</th><th>Status</th></tr></thead>
            <tbody>@foreach ($promos as $p)<tr><td class="mono"><b>{{ $p->code }}</b></td><td>{{ $p->description }}</td><td class="num">${{ number_format($p->credit, 2) }}</td>
                <td class="help">{{ $p->starts_on?->format('n/j/y') ?? 'any' }} – {{ $p->ends_on?->format('n/j/y') ?? 'open' }}</td><td class="num">{{ $promoUses[$p->code] ?? 0 }}</td>
                <td>@include('admin.partials.pill', ['text' => $p->isLive() ? 'Live' : 'Off', 'tone' => $p->isLive() ? 'ok' : ''])</td></tr>@endforeach</tbody></table></div>
            <form method="post" action="{{ route('rodeo.promos.save') }}" class="panel-body form-row">@csrf
                <input name="code" required placeholder="CODE" aria-label="Code" style="width:12ch"><input name="description" required placeholder="Description" aria-label="Description" style="flex:1">
                <input name="credit" type="number" step="0.01" min="0" value="0" aria-label="Credit $" style="width:9ch">
                <input name="starts_on" type="date" aria-label="Starts"><input name="ends_on" type="date" aria-label="Ends">
                <label class="check"><input type="hidden" name="active" value="0"><input type="checkbox" name="active" value="1" checked> Active</label>
                <button class="btn sm">Add / Update</button>
            </form>
        </div>
    </div>
@endsection
