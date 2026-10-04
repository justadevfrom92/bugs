@extends('admin.layouts.app')

@section('crumb', 'Account '.$c->account)

@section('content')
    @include('admin.partials.page-head', [
        'title' => $c->name,
        'sub' => 'Account <span class="mono">'.e($c->account).'</span> · Ticket <span class="mono">'.e($c->ticket).'</span>',
        'actions' => '<form method="post" action="'.route('corral.customers.bookmark', $c).'" class="inline">'.csrf_field().'<button class="btn ghost">'.($bookmarked ? '★ Bookmarked' : '☆ Bookmark').'</button></form>'
            .'<a class="btn" href="'.route('corral.orders.create', ['renew' => $c->account]).'">Renew / Change Plan</a>',
    ])

    <div class="grid-4">
        <div class="stat"><span>Status</span><div style="margin-top:10px">@include('admin.partials.pill', ['text' => $c->status, 'tone' => $c->statusTone()])</div>
            @if ($c->exception)<div style="margin-top:6px">@include('admin.partials.pill', ['text' => $c->exception, 'tone' => 'bad'])</div>@endif</div>
        <div @class(['stat', 'alert' => $c->balance > 150])><span>Balance</span><strong>${{ number_format($c->balance, 2) }}</strong></div>
        <div class="stat"><span>Plan</span><strong style="font-size:1rem;margin-top:8px">{{ $c->plan?->name ?? '—' }}</strong><small class="mono">{{ $c->plan?->internal }}</small></div>
        <div class="stat"><span>{{ config('brand.rewards') }} Stars</span><strong>{{ number_format($c->stars) }}</strong></div>
    </div>

    <div class="panel" data-tabs>
        <div class="tabs" role="tablist">
            @foreach (['account' => 'Account', 'service' => 'Service', 'billing' => 'Billing', 'payments' => 'Payments', 'notes' => 'Notes', 'products' => 'Products', 'history' => 'History ('.number_format($historyTotal).')'] as $key => $label)
                <button type="button" role="tab" data-tab="{{ $key }}">{{ $label }}</button>
            @endforeach
        </div>
        <div class="panel-body">
            <div data-pane="account"><dl class="kv">
                <dt>Customer</dt><dd>{{ $c->name }}</dd><dt>Type</dt><dd>{{ $c->type }}</dd>
                <dt>Phone</dt><dd>{{ $c->phone }}</dd><dt>Email</dt><dd>{{ $c->email }}</dd>
                <dt>Created</dt><dd>{{ $c->created_at->toDateString() }}</dd><dt>Source</dt><dd>{{ $c->source }}</dd>
                <dt>Enrollment</dt><dd>{{ $c->enrollment_type }}@if ($c->start_date) · starts {{ $c->start_date->toDateString() }}@endif</dd>
                <dt>AutoPay</dt><dd>@include('admin.partials.pill', $c->autopay ? ['text' => 'Enrolled', 'tone' => 'ok'] : ['text' => 'Not enrolled'])</dd>
                <dt>Paperless</dt><dd>@include('admin.partials.pill', $c->paperless ? ['text' => 'Enrolled', 'tone' => 'ok'] : ['text' => 'Not enrolled'])</dd>
            </dl></div>

            <div data-pane="service" hidden><dl class="kv">
                <dt>Service Address</dt><dd>{{ $c->address }}, {{ $c->city }}, TX {{ $c->zip }}</dd>
                <dt>ESIID</dt><dd class="mono">{{ $c->esiid ?? '—' }}</dd>
                <dt>TDSP</dt><dd>{{ $c->market?->name }} · {{ $c->market?->description }}</dd>
                <dt>Plan</dt><dd>{{ $c->plan?->name }} ({{ $c->plan?->internal }})</dd>
                <dt>Status</dt><dd>
                    <form method="post" action="{{ route('corral.customers.status', $c) }}" class="form-row">
                        @csrf @method('patch')
                        <select name="status" aria-label="Status">@foreach (array_keys(config('admin.customer_statuses')) as $s)<option @selected($s === $c->status)>{{ $s }}</option>@endforeach</select>
                        <button class="btn sm">Update</button>
                    </form>
                </dd>
            </dl></div>

            <div data-pane="billing" hidden><div class="table-wrap"><table>
                <thead><tr><th>Bill</th><th>Date</th><th class="num">kWh</th><th class="num">Amount</th></tr></thead>
                <tbody>@forelse ($c->bills as $b)
                    <tr><td class="mono">{{ $b->reference }}</td><td>{{ $b->billed_on->toDateString() }}</td><td class="num">{{ number_format($b->kwh) }}</td><td class="num">${{ number_format($b->amount, 2) }}</td></tr>
                @empty <tr><td colspan="4" class="empty">No bills yet.</td></tr> @endforelse</tbody>
            </table></div></div>

            <div data-pane="payments" hidden><div class="table-wrap"><table>
                <thead><tr><th>Payment</th><th>Date</th><th>Method</th><th>Source</th><th>Status</th><th class="num">Amount</th><th></th></tr></thead>
                <tbody>@forelse ($c->payments as $p)
                    <tr><td class="mono">{{ $p->reference }}</td><td>{{ $p->paid_on->toDateString() }}</td><td>{{ $p->method }}</td><td>{{ $p->source }}</td>
                        <td>@include('admin.partials.pill', ['text' => $p->status, 'tone' => ['Success' => 'ok', 'Failed' => 'bad', 'Reversed' => 'warn'][$p->status] ?? ''])</td>
                        <td class="num">${{ number_format($p->amount, 2) }}</td>
                        <td>@if ($canRefund && $p->status === 'Success')
                            <form method="post" action="{{ route('corral.payments.reverse', $p) }}" class="inline" data-confirm="Reverse payment?|Payment {{ $p->reference }} for ${{ number_format($p->amount, 2) }} will be reversed and added back to the balance.|Reverse Payment">@csrf<button class="btn sm ghost">Reverse</button></form>
                        @endif</td></tr>
                @empty <tr><td colspan="7" class="empty">No payments yet.</td></tr> @endforelse</tbody>
            </table></div></div>

            <div data-pane="notes" hidden>
                <form method="post" action="{{ route('corral.customers.notes', $c) }}" class="form-row" style="margin-bottom:16px">
                    @csrf
                    <div class="field grow"><label for="note-body">Add Note</label><input id="note-body" name="body" required placeholder="What happened on this call?"></div>
                    <div class="field"><label for="note-disp">Disposition</label><select id="note-disp" name="disposition"><option value="">—</option>@foreach ($dispositions as $d)<option>{{ $d }}</option>@endforeach</select></div>
                    <button class="btn">Add Note</button>
                </form>
                @forelse ($c->notes as $n)
                    <div class="note"><small>{{ $n->created_at->format('Y-m-d H:i') }} · {{ $n->author }}@if ($n->disposition) · {{ $n->disposition }}@endif</small>{{ $n->body }}</div>
                @empty <p class="muted">No notes yet.</p> @endforelse
            </div>

            <div data-pane="products" hidden><div class="table-wrap"><table>
                <thead><tr><th>Product</th><th>Status</th></tr></thead>
                <tbody>
                    @foreach ([[config('brand.rewards'), true], ['AutoPay', $c->autopay], ['Paperless', $c->paperless], ['Peak Perks', $c->peak_perks]] as [$name, $on])
                        <tr><td>{{ $name }}</td><td>@include('admin.partials.pill', $on ? ['text' => 'Enrolled', 'tone' => 'ok'] : ['text' => 'Not enrolled'])</td></tr>
                    @endforeach
                </tbody>
            </table></div></div>

            <div data-pane="history" hidden style="display:flex;flex-direction:column;gap:20px">
                @include('admin.history._counts', ['counts' => $historyCounts, 'filters' => $historyFilters, 'anchor' => '#history'])

                <div class="panel">
                    <div class="panel-head"><h2>Timeline</h2>
                        <span class="actions">
                            <span class="muted">Showing {{ $historyItems->count() }} of {{ number_format($historyShown) }}</span>
                            @if ($historyFilters)<a class="btn sm ghost" href="{{ route('corral.customers.show', $c) }}#history">Clear filter ({{ $historyFilters['hmodel'] ?? $historyFilters['hgroup'] }})</a>@endif
                            @if ($historyItems->count() < $historyShown)<a class="btn sm ghost" href="{{ request()->fullUrlWithQuery(['hall' => 1]) }}#history">Show all</a>@endif
                        </span>
                    </div>
                    @include('admin.history._list', ['items' => $historyItems, 'route' => 'corral.history.show'])
                </div>

                <div class="grid-2">
                    <div class="panel"><div class="panel-head"><h2>Usage History</h2><span class="muted">kWh by month</span></div>
                        <div class="table-wrap"><table>
                            <thead><tr><th>Year</th>@foreach (range(1, 12) as $m)<th class="num">{{ date('M', mktime(0, 0, 0, $m, 1)) }}</th>@endforeach</tr></thead>
                            <tbody>@forelse ($usageHistory as $year => $months)
                                <tr><td><b>{{ $year }}</b></td>@foreach (range(1, 12) as $m)<td class="num">{{ isset($months[$m]) ? number_format($months[$m]) : '-' }}</td>@endforeach</tr>
                            @empty <tr><td colspan="13" class="empty">No usage yet.</td></tr> @endforelse</tbody>
                        </table></div>
                    </div>
                    <div class="panel"><div class="panel-head"><h2>Rewards History</h2><span class="muted">{{ config('brand.rewards') }}</span></div>
                        <div class="table-wrap"><table>
                            <thead><tr><th>Date</th><th>Reward</th><th class="num">Stars</th><th class="num">Net</th></tr></thead>
                            <tbody>@forelse ($rewardsHistory as $r)
                                <tr><td>{{ $r['date']->format('n/j/Y') }}</td><td>{{ $r['reason'] }}</td><td class="num">+{{ rtrim(rtrim(number_format($r['stars'], 1), '0'), '.') }}</td><td class="num">{{ number_format($r['net'], 1) }}</td></tr>
                            @empty <tr><td colspan="4" class="empty">No stars earned yet.</td></tr> @endforelse</tbody>
                        </table></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
