@extends('admin.layouts.app')

@section('content')
    @include('admin.partials.page-head', ['title' => 'Refunds', 'sub' => 'Refunds of credit balances and deposits. Anyone in Strongbox can request one; approving needs the refunds right.'])
    <div class="grid-2" style="grid-template-columns:1fr 340px;align-items:start">
        <div>
            <div class="panel"><div class="panel-head"><h2>Open</h2></div><div class="table-wrap"><table>
                <thead><tr><th>Requested</th><th>Account</th><th>Kind</th><th class="num">Amount</th><th>Reason</th><th>Method</th><th>Status</th><th></th></tr></thead>
                <tbody>@forelse ($open as $r)
                    <tr><td>{{ $r->created_at->format('n/j/Y') }}<br><span class="help">{{ $r->requester?->name }}</span></td><td>{{ $r->customer?->account }}<br><span class="help">{{ $r->customer?->name }}</span></td>
                        <td>{{ ucfirst($r->kind) }}</td><td class="num">${{ number_format($r->amount, 2) }}</td><td>{{ $r->reason }}</td><td>{{ $r->method }}</td>
                        <td>@include('admin.partials.pill', ['text' => ucfirst($r->status), 'tone' => $r->status === 'approved' ? 'ok' : 'info'])</td>
                        <td class="actions">@can('refunds')
                            @if ($r->status === 'requested')
                                @foreach (['approve' => 'Approve', 'reject' => 'Reject'] as $do => $l)<form method="post" action="{{ route('strongbox.refunds.decide', $r) }}" class="inline">@csrf<input type="hidden" name="do" value="{{ $do }}"><button class="btn sm {{ $do === 'approve' ? '' : 'ghost' }}">{{ $l }}</button></form>@endforeach
                            @else
                                <form method="post" action="{{ route('strongbox.refunds.decide', $r) }}" class="inline" data-confirm="Mark refund paid?|Confirm the {{ strtolower($r->method) }} for ${{ number_format($r->amount, 2) }} was sent.|Mark Paid">@csrf<input type="hidden" name="do" value="paid"><button class="btn sm">Mark Paid</button></form>
                            @endif
                        @else <span class="help">Needs refunds right</span> @endcan</td></tr>
                @empty <tr><td colspan="8" class="empty">No open refunds.</td></tr> @endforelse</tbody>
            </table></div></div>
            <div class="panel"><div class="panel-head"><h2>Credit Balances Without a Refund</h2></div><div class="table-wrap"><table>
                <tbody>@forelse ($credits as $c)<tr><td>{{ $c->account }}</td><td>{{ $c->name }}</td><td>{{ $c->status }}</td><td class="num">${{ number_format(abs($c->balance), 2) }} credit</td></tr>
                @empty <tr><td class="empty">None.</td></tr> @endforelse</tbody>
            </table></div></div>
            <div class="panel"><div class="panel-head"><h2>Recently Closed</h2></div><div class="table-wrap"><table>
                <tbody>@forelse ($done as $r)<tr><td>{{ $r->updated_at->format('n/j/Y') }}</td><td>{{ $r->customer?->account }}</td><td>{{ ucfirst($r->kind) }}</td><td class="num">${{ number_format($r->amount, 2) }}</td><td>@include('admin.partials.pill', ['text' => ucfirst($r->status), 'tone' => $r->status === 'paid' ? 'ok' : ''])</td><td>{{ $r->decider?->name }}</td></tr>
                @empty <tr><td class="empty">None yet.</td></tr> @endforelse</tbody>
            </table></div></div>
        </div>
        <form method="post" action="{{ route('strongbox.refunds.request') }}" class="panel">@csrf
            <div class="panel-head"><h2>Request a Refund</h2></div>
            <div class="panel-body" style="display:flex;flex-direction:column;gap:12px">
                <div class="field"><label for="f-account">Account #</label><input id="f-account" name="account" required value="{{ old('account') }}"></div>
                <div class="field"><label for="f-kind">Refund Of</label><select id="f-kind" name="kind"><option value="balance">Credit balance</option><option value="deposit">Deposit</option></select></div>
                <div class="field"><label for="f-amount">Amount ($)</label><input id="f-amount" name="amount" type="number" step="0.01" min="0.01" required value="{{ old('amount') }}"></div>
                <div class="field"><label for="f-method">Send By</label><select id="f-method" name="method"><option>Check</option><option>Card</option><option>ACH</option></select></div>
                <div class="field"><label for="f-reason">Reason</label><input id="f-reason" name="reason" required value="{{ old('reason') }}"></div>
                <button class="btn cyan">Request Refund</button>
            </div>
        </form>
    </div>
@endsection
