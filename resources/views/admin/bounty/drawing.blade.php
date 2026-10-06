@extends('admin.layouts.app')

@section('content')
    @include('admin.partials.page-head', ['title' => 'Monthly Drawing', 'sub' => 'Customers enter by redeeming the free "Monthly Drawing" offer. The winner is saved to Sheriff → Data → Monthly Drawing.'])
    <div class="grid-2" style="grid-template-columns:1fr 340px;align-items:start">
        <div class="panel"><div class="panel-head"><h2>Entries for {{ $month }}</h2><span class="muted">{{ $entries->count() }}</span></div><div class="table-wrap"><table>
            <tbody>@forelse ($entries as $e)<tr><td>{{ $e->created_at->format('n/j/Y g:i A') }}</td><td>{{ $e->customer?->account }}</td><td>{{ $e->customer?->name }}</td></tr>
            @empty <tr><td class="empty">No entries yet this month.</td></tr> @endforelse</tbody>
        </table></div></div>
        <div style="display:flex;flex-direction:column;gap:20px">
            <form method="post" action="{{ route('bounty.draw') }}" class="panel">@csrf
                <div class="panel-head"><h2>Draw a Winner</h2></div>
                <div class="panel-body" style="display:flex;flex-direction:column;gap:12px">
                    <div class="field"><label for="prize">Prize</label><input id="prize" name="prize" required value="$100 Bill Credit"></div>
                    <button class="btn cyan" @disabled($entries->isEmpty())>Draw Winner</button>
                </div>
            </form>
            <div class="panel"><div class="panel-head"><h2>Past Winners</h2></div><div class="table-wrap"><table>
                <tbody>@forelse ($past as $r)<tr><td>{{ $r->cells[0] ?? '' }}</td><td>{{ $r->cells[1] ?? '' }}</td><td class="mono">{{ ($r->cells[2] ?? '') ?: '—' }}</td></tr>
                @empty <tr><td class="empty">None.</td></tr> @endforelse</tbody>
            </table></div></div>
        </div>
    </div>
@endsection
