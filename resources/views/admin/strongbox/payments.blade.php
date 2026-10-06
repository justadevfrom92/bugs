@extends('admin.layouts.app')

@section('content')
    @include('admin.partials.page-head', ['title' => 'Payments', 'sub' => 'Every payment, with reversal (needs the refunds right). Record checks, money orders and cash received.'])

    <div class="grid-2" style="grid-template-columns:1fr 340px;align-items:start">
        <div>
            <form method="get" class="panel"><div class="panel-body form-row">
                <div class="field"><label for="start">From</label><input id="start" name="start" type="date" value="{{ $f['start'] }}"></div>
                <div class="field"><label for="end">To</label><input id="end" name="end" type="date" value="{{ $f['end'] }}"></div>
                <div class="field"><label for="status">Status</label><select id="status" name="status"><option value="">Any</option>@foreach (['Success', 'Pending', 'Failed', 'Reversed'] as $s)<option @selected($f['status'] === $s)>{{ $s }}</option>@endforeach</select></div>
                <div class="field"><label for="account">Account</label><input id="account" name="account" value="{{ $f['account'] }}" style="width:13ch"></div>
                <button class="btn">Search</button>
            </div></form>
            <div class="panel"><div class="panel-head"><h2>Payments</h2><span class="muted">${{ number_format($total, 2) }} successful</span></div><div class="table-wrap"><table>
                <thead><tr><th>Date</th><th>Account</th><th class="num">Amount</th><th>Method</th><th>Source</th><th>Kind</th><th>Status</th><th></th></tr></thead>
                <tbody>@forelse ($payments as $p)
                    <tr><td>{{ $p->paid_on?->format('n/j/Y') }}</td><td>{{ $p->customer?->account }}<br><span class="help">{{ $p->customer?->name }}</span></td>
                        <td class="num">${{ number_format($p->amount, 2) }}</td><td>{{ $p->method }}</td><td>{{ $p->source }}</td><td>{{ $p->kind }}</td>
                        <td>@include('admin.partials.pill', ['text' => $p->status, 'tone' => ['Success' => 'ok', 'Pending' => 'info', 'Reversed' => 'warn'][$p->status] ?? 'bad'])</td>
                        <td>@if ($p->status === 'Success')@can('refunds')<form method="post" action="{{ route('strongbox.payments.reverse', $p) }}" class="inline" data-confirm="Reverse this payment?|${{ number_format($p->amount, 2) }} goes back on account {{ $p->customer?->account }}.|Reverse">@csrf<button class="btn sm ghost">Reverse</button></form>@endcan @endif</td></tr>
                @empty <tr><td colspan="8" class="empty">No payments match.</td></tr> @endforelse</tbody>
            </table></div>@include('admin.partials.pager', ['p' => $payments])</div>
        </div>
        <form method="post" action="{{ route('strongbox.payments.record') }}" class="panel">@csrf
            <div class="panel-head"><h2>Record a Payment</h2></div>
            <div class="panel-body" style="display:flex;flex-direction:column;gap:12px">
                <div class="field"><label for="r-account">Account #</label><input id="r-account" name="account" required value="{{ old('account') }}"></div>
                <div class="field"><label for="r-amount">Amount ($)</label><input id="r-amount" name="amount" type="number" step="0.01" min="0.01" required value="{{ old('amount') }}"></div>
                <div class="field"><label for="r-kind">For</label><select id="r-kind" name="kind"><option>Balance Payment</option><option>Deposit</option></select></div>
                <div class="field"><label for="r-method">Method</label><select id="r-method" name="method">@foreach (['Check', 'Money Order', 'Cash', 'Wire'] as $mth)<option>{{ $mth }}</option>@endforeach</select></div>
                <div class="field"><label for="r-ref">Check # / Reference</label><input id="r-ref" name="reference" value="{{ old('reference') }}"></div>
                <button class="btn cyan">Record Payment</button>
            </div>
        </form>
    </div>
@endsection
