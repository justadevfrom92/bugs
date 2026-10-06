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
@endsection
