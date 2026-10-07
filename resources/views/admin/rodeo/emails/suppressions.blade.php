@extends('admin.layouts.app')

@section('content')
    @include('admin.partials.page-head', ['title' => 'Suppression List',
        'sub' => 'No email goes to these addresses: not account emails from Corral, not campaigns, not tests. Remove an address to let emails go to it again.',
        'actions' => '<a class="btn cyan" href="'.route('rodeo.emails.suppressions.create').'">Add Address</a>'])

    <div class="search-layout">
        <aside class="search-side">
            <form method="get" class="panel"><div class="panel-head"><h2>Search List</h2></div><div class="panel-body">
                <div class="field"><label for="q">Email or Account</label><input id="q" name="q" value="{{ $f['q'] ?? '' }}"></div>
                <div class="field"><label for="reason">Reason</label><select id="reason" name="reason"><option value="">Any</option>@foreach (\App\Models\EmailSuppression::REASONS as $v => $l)<option value="{{ $v }}" @selected(($f['reason'] ?? '') === $v)>{{ $l }} ({{ $counts[$v] ?? 0 }})</option>@endforeach</select></div>
                <button class="btn">Search</button>@if (array_filter($f))<a class="btn ghost" href="{{ route('rodeo.emails.suppressions') }}">Clear</a>@endif
            </div></form>
        </aside>
        <div class="search-main">
            <div class="panel"><div class="table-wrap"><table class="table">
                <thead><tr><th>Email</th><th>Account</th><th>Reason</th><th>Note</th><th>Added</th><th></th></tr></thead>
                <tbody>@forelse ($list as $s)
                    <tr><td>{{ $s->email }}</td>
                        <td>@if ($s->customer)<a class="mono" href="{{ route('rodeo.emails.sent', ['account' => $s->customer->account]) }}" title="Every email to this account">{{ $s->customer->account }}</a><div class="muted">{{ $s->customer->name }}</div>@else<span class="muted">—</span>@endif</td>
                        <td>@include('admin.partials.pill', ['text' => \App\Models\EmailSuppression::REASONS[$s->reason] ?? $s->reason, 'tone' => in_array($s->reason, ['bounce', 'complaint'], true) ? 'bad' : ''])</td>
                        <td class="wrap">{{ $s->note }}</td>
                        <td class="nowrap">{{ $s->created_at->format('n/j/Y') }}<div class="muted">{{ $s->user?->name ?? 'Mail service' }}</div></td>
                        <td class="actions"><form method="post" action="{{ route('rodeo.emails.suppressions.destroy', $s) }}" class="inline" data-confirm="Remove {{ $s->email }}?|Emails can go to this address again.|Remove">@csrf @method('delete')<button class="btn sm ghost">Remove</button></form></td></tr>
                @empty <tr><td colspan="6" class="empty">No addresses {{ array_filter($f) ? 'match' : 'on the list' }}.</td></tr> @endforelse</tbody>
            </table></div>
            @include('admin.partials.pager', ['p' => $list])</div>
        </div>
    </div>
@endsection
