@extends('admin.layouts.app')

@section('crumb', $block->exists ? 'Content Block '.$block->id : 'Add a Block')

@section('content')
    @include('admin.partials.page-head', ['title' => $block->exists ? 'Editing Content Block '.$block->id : 'Add a Content Block', 'sub' => e($block->name)])
    <div class="grid-2">
        <form method="post" action="{{ $block->exists ? route('lando.blocks.update', $block) : route('lando.blocks.store') }}" class="panel"><div class="panel-body">
            @csrf @if ($block->exists) @method('put') @endif
            <div class="form-grid" style="grid-template-columns:1fr">
                <label for="name">Name</label><input id="name" name="name" required value="{{ old('name', $block->name) }}">
                <label for="b-html">HTML</label><textarea id="b-html" name="html" spellcheck="false">{{ old('html', $block->html) }}</textarea>
            </div>
            <div class="actions" style="margin-top:16px"><button class="btn cyan">Save Block</button><a class="btn ghost" href="{{ route('lando.blocks.index') }}">Cancel</a></div>
            <p class="help" style="margin-top:10px">Ctrl+S saves.</p>
        </div></form>
        <div class="panel"><div class="panel-head"><h2>Preview</h2><span class="muted">shortcodes shown as-is</span></div>
            <div class="panel-body"><iframe id="b-prev" title="Block preview" sandbox="" style="width:100%;min-height:340px;border:1px solid var(--line);border-radius:6px;background:#fff"></iframe></div>
        </div>
    </div>
@endsection
