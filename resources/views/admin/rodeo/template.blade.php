@extends('admin.layouts.app')

@section('crumb', $template->exists ? $template->name : 'New Template')

@section('content')
    @include('admin.partials.page-head', ['title' => $template->exists ? $template->name : 'New Template', 'sub' => 'Fill-ins: {{first_name}} {{account}} {{balance}} {{plan}} and {{survey:slug}} for a survey link.'])
    <div class="grid-2">
        <form method="post" action="{{ $template->exists ? route('rodeo.templates.update', $template) : route('rodeo.templates.store') }}" class="panel">@csrf @if ($template->exists) @method('put') @endif
            <div class="panel-body form-grid" style="grid-template-columns:1fr">
                <label for="name">Name</label><input id="name" name="name" required value="{{ old('name', $template->name) }}">
                <label for="subject">Subject</label><input id="subject" name="subject" required value="{{ old('subject', $template->subject) }}">
                <label for="b-html">Body (HTML)</label><textarea id="b-html" name="body" spellcheck="false">{{ old('body', $template->body) }}</textarea>
            </div>
            <div class="panel-body"><button class="btn cyan">Save Template</button></div>
        </form>
        <div class="panel"><div class="panel-head"><h2>Preview</h2>@if ($template->exists && $sample)<span class="muted">for {{ $sample->first_name }} ({{ $sample->account }})</span>@endif</div>
            <div class="panel-body"><iframe id="b-prev" title="Email preview" sandbox="" style="width:100%;min-height:360px;border:1px solid var(--line);border-radius:6px;background:#fff"></iframe></div>
        </div>
    </div>

    @if ($template->exists)
    <div class="panel" id="test" style="margin-top:20px">
        <div class="panel-head"><h2>Send a Test</h2><span class="muted">Sends the saved template, subject marked [TEST], to up to {{ \App\Http\Controllers\Admin\Rodeo\TemplateTestController::MAX }} people</span></div>
        @if (! $delivers)<div class="banner-note" style="margin:12px 18px 0">MAIL_MAILER in .env is "{{ config('mail.default') }}", so tests are written to the mail log, not delivered. Set it to smtp (or ses, postmark…) with its MAIL_* settings to send real email.</div>@endif
        @if ($r = session('test_result'))<div class="flash {{ $r['ok'] ? 'ok' : 'bad' }}" role="status" style="margin:12px 18px 0">{{ $r['message'] }}</div>@endif
        <div data-tabs data-tab-initial="{{ old('mode', session('test_result.mode')) }}">
            <div class="tabs" role="tablist">
                @foreach (\App\Http\Controllers\Admin\Rodeo\TemplateTestController::MODES as $mode => $label)<button type="button" role="tab" data-tab="{{ $mode }}">{{ $label }}</button>@endforeach
            </div>
            @php $sampleField = '<label for="SID">Fill-ins from account</label><input id="SID" name="sample" value="'.e(old('sample', $sample?->account)).'" inputmode="numeric" style="max-width:16ch">'; @endphp
            <form data-pane="emails" method="post" action="{{ route('rodeo.templates.test', $template) }}" class="panel-body form-grid">@csrf<input type="hidden" name="mode" value="emails">
                <label for="t-emails">Send to</label><textarea id="t-emails" name="emails" placeholder="name@example.com, other@example.com" style="min-height:70px;font-family:inherit">{{ old('emails', request()->user()->email) }}</textarea>
                {!! str_replace('SID', 't-sample1', $sampleField) !!}
                <span></span><div><button class="btn cyan">Send Test</button></div>
            </form>
            <form data-pane="accounts" method="post" action="{{ route('rodeo.templates.test', $template) }}" class="panel-body form-grid" hidden>@csrf<input type="hidden" name="mode" value="accounts">
                <label for="t-accounts">Account numbers</label><textarea id="t-accounts" name="accounts" placeholder="1219000000, 1219000001" style="min-height:70px;font-family:inherit">{{ old('accounts') }}</textarea>
                <span></span><p class="muted" style="margin:0">Each account gets the email at its address on file, filled in with its own name, balance and plan.</p>
                <span></span><div><button class="btn cyan">Send Test</button></div>
            </form>
            <form data-pane="bookmarks" method="post" action="{{ route('rodeo.templates.test', $template) }}" class="panel-body" hidden data-confirm="Send a test to your bookmarks?|{{ $bookmarks }} bookmarked {{ Str::plural('account', $bookmarks) }} with an email address will get it.|Send">@csrf<input type="hidden" name="mode" value="bookmarks">
                <p style="margin-top:0">Your Corral bookmarks: <strong>{{ $bookmarks }}</strong> {{ Str::plural('account', $bookmarks) }} with an email address. Each gets its own fill-ins.</p>
                <button class="btn cyan" @disabled(! $bookmarks)>Send Test</button>
            </form>
            <form data-pane="team" method="post" action="{{ route('rodeo.templates.test', $template) }}" class="panel-body form-grid" hidden>@csrf<input type="hidden" name="mode" value="team">
                <label for="t-role">Team</label><select id="t-role" name="role_id">@foreach ($roles as $role)<option value="{{ $role->id }}">{{ $role->name }} ({{ $role->users_count }} {{ Str::plural('person', $role->users_count) }})</option>@endforeach</select>
                {!! str_replace('SID', 't-sample2', $sampleField) !!}
                <span></span><div><button class="btn cyan">Send Test</button></div>
            </form>
            <form data-pane="category" method="post" action="{{ route('rodeo.templates.test', $template) }}" class="panel-body form-grid" hidden data-confirm="Send a test to customers?|This emails real customers in that category, marked [TEST].|Send">@csrf<input type="hidden" name="mode" value="category">
                <label for="t-status">Account status</label><select id="t-status" name="status"><option value="">Any</option>@foreach (array_keys(config('admin.customer_statuses')) as $s)<option>{{ $s }}</option>@endforeach</select>
                <label for="t-type">Customer type</label><select id="t-type" name="type"><option value="">Any</option><option>Residential</option><option>Small Business</option></select>
                <label for="t-market">Market</label><select id="t-market" name="market_id"><option value="">Any</option>@foreach ($markets as $m)<option value="{{ $m->id }}">{{ $m->name }}</option>@endforeach</select>
                <label for="t-limit">How many</label><input id="t-limit" name="limit" type="number" min="1" max="{{ \App\Http\Controllers\Admin\Rodeo\TemplateTestController::MAX }}" value="5" style="max-width:8ch">
                <span></span><div><button class="btn cyan">Send Test</button></div>
            </form>
        </div>
        @if ($tests->isNotEmpty())
        <div class="panel-head"><h2>Recent Tests</h2></div>
        <div class="table-wrap"><table class="table"><thead><tr><th>When</th><th>By</th><th>Sent To</th><th>Recipients</th><th>Result</th></tr></thead>
            <tbody>@foreach ($tests as $t)
                <tr><td class="nowrap">{{ $t->created_at->format('n/j/Y g:i A') }}</td><td>{{ $t->user?->name }}</td>
                    <td>{{ \App\Http\Controllers\Admin\Rodeo\TemplateTestController::MODES[$t->mode] ?? $t->mode }}@if ($t->target)<div class="muted">{{ Str::limit($t->target, 80) }}</div>@endif</td>
                    <td>@foreach ($t->recipients as $r)<div>{{ $r['email'] }}@isset($r['account']) <span class="muted mono">{{ $r['account'] }}</span>@endisset @if (($r['status'] ?? '') === 'failed')<span class="muted">— {{ $r['error'] ?? 'failed' }}</span>@endif</div>@endforeach</td>
                    <td>@include('admin.partials.pill', ['text' => ['sent' => 'Sent', 'logged' => 'Logged only', 'failed' => 'Failed', 'partial' => 'Partly sent'][$t->status] ?? $t->status, 'tone' => ['sent' => 'ok', 'failed' => 'bad', 'partial' => 'warn'][$t->status] ?? ''])</td></tr>
            @endforeach</tbody></table></div>
        @endif
    </div>
    @endif
@endsection
