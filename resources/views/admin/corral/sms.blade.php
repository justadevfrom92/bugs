@extends('admin.layouts.app')

@section('content')
    @include('admin.partials.page-head', ['title' => 'SMS', 'sub' => 'Text conversations with customers. '.($unanswered ? '<b>'.$unanswered.'</b> waiting on a reply.' : 'Nothing waiting on a reply.')])

    @unless ($ready)
        <div class="alert info"><b>Texts are logged, not sent.</b> Add the Twilio settings to .env (see Sheriff → APIs) to send texts.</div>
    @endunless

    <div class="sms">
        <div class="panel"><div class="panel-head"><h2>Conversations</h2></div>
            <div class="panel-body" style="padding-bottom:0"><form method="get" class="form-row">
                <input name="q" value="{{ $q }}" placeholder="Name, phone or account" aria-label="Search conversations" style="flex:1">
                <button class="btn sm">Search</button></form></div>
            <div class="threads">
                @forelse ($threads as $t)
                    <a href="{{ route('corral.sms', ['account' => $t->customer?->account, 'q' => $q ?: null]) }}" @class(['thread', 'on' => $open && $open->id === $t->customer_id])>
                        <b>{{ $t->customer?->name ?? $t->phone }}</b>@if ($t->direction === 'in')<span class="count-pill">new</span>@endif
                        <span class="help">{{ $t->created_at->format('n/j g:i A') }}</span>
                        <span class="snippet">{{ $t->direction === 'in' ? '' : 'You: ' }}{{ \Illuminate\Support\Str::limit($t->body, 60) }}</span>
                    </a>
                @empty <p class="empty">No conversations{{ $q !== '' ? ' match' : '' }}.</p> @endforelse
            </div>
        </div>

        <div class="panel">
            @if ($open)
                <div class="panel-head"><h2>{{ $open->name }}</h2><span class="actions"><span class="mono muted">{{ $open->phone }}</span><a class="btn sm ghost" href="{{ route('corral.customers.show', $open) }}">Open Account</a></span></div>
                <div class="panel-body bubbles">
                    @forelse ($messages as $m)
                        <div @class(['bubble', 'in' => $m->direction === 'in'])>{{ $m->body }}
                            <small>{{ $m->created_at->format('n/j/Y g:i A') }}@if ($m->direction === 'out') · {{ $m->status }}@endif</small></div>
                    @empty <p class="muted">No texts yet.</p> @endforelse
                </div>
                <form method="post" action="{{ route('corral.sms.send') }}" class="panel-body form-row" style="border-top:1px solid var(--line)">@csrf
                    <input type="hidden" name="account" value="{{ $open->account }}">
                    <textarea name="message" maxlength="480" required placeholder="Reply to {{ $open->first_name ?: $open->name }}" aria-label="Message" style="flex:1;min-height:60px"></textarea>
                    <button class="btn">Send</button>
                </form>
            @else
                <div class="panel-head"><h2>New Text</h2></div>
                <form method="post" action="{{ route('corral.sms.send') }}" class="panel-body form-grid">@csrf
                    <label for="account">Account #</label><input id="account" name="account" value="{{ old('account') }}" inputmode="numeric" required>
                    <label for="message">Message</label><textarea id="message" name="message" maxlength="480" required style="font-family:inherit;min-height:120px">{{ old('message') }}</textarea>
                    <div class="actions" style="grid-column:1/-1"><button class="btn">Send</button></div>
                </form>
            @endif
        </div>
    </div>
@endsection
