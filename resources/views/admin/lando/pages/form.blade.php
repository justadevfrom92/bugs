@extends('admin.layouts.app')

@section('crumb', $page->exists ? 'Edit Page' : 'Add a Page')

{{-- Laid out like the original Lando "Edit Page": Basic, Meta Data, Content, Page Components, Edits. --}}
@section('content')
    @include('admin.partials.page-head', [
        'title' => $page->exists ? 'Edit Page: '.$page->title : 'Add a Page',
        'sub' => e($page->site?->name).($page->exists ? ' · <span class="mono">'.e($page->url()).'</span>' : ''),
        'actions' => $page->exists && ! $page->redirect ? '<a class="btn ghost" href="'.e($page->liveUrl()).'" target="_blank" rel="noopener">View Page Live</a>' : null,
    ])

    @if ($page->exists)
        <nav class="jump" aria-label="Sections">@foreach (['basic' => 'Basic', 'meta' => 'Meta Data', 'content' => 'Content', 'components' => 'Page Components', 'edits' => 'Edits'] as $id => $l)<a href="#{{ $id }}">{{ $l }}</a>@endforeach</nav>
    @endif
    @if ($page->file)
        <div class="alert info"><b>Built-in page.</b> This page's layout is the file <span class="mono">public/{{ $page->file }}</span>. Its title, meta data and status are used here; content areas don't apply. Add components to its top and bottom zones below.</div>
    @endif

    <form method="post" action="{{ $page->exists ? app_route('pages.update', $page) : app_route('pages.store') }}" style="display:flex;flex-direction:column;gap:20px">
        @csrf @if ($page->exists) @method('put') @endif

        <div class="panel" id="basic"><div class="panel-head"><h2>Basic</h2><button class="btn cyan">Save Page</button></div><div class="panel-body form-grid">
            @unless ($page->exists)
                <label for="site_id">Site</label>
                <select id="site_id" name="site_id">@foreach ($sites as $s)<option value="{{ $s->id }}" @selected(old('site_id', $page->site_id) == $s->id)>{{ $s->name }} ({{ $s->domain }})</option>@endforeach</select>
            @endunless
            <label for="title">Title</label><input id="title" name="title" required value="{{ old('title', $page->title) }}">
            <label for="path">Page Slug / Path</label><input id="path" name="path" required class="mono" value="{{ old('path', $page->path) }}" placeholder="get-to-learnin/new-article" @readonly($page->file)>
            <label for="template_id">Template</label>
            <select id="template_id" name="template_id"><option value="">—</option>@foreach ($templates as $t)<option value="{{ $t->id }}" @selected(old('template_id', $page->template_id) == $t->id)>{{ $t->name }}</option>@endforeach</select>
            <label for="status">Status</label>
            <select id="status" name="status">@foreach (['Published', 'Draft'] as $s)<option @selected(old('status', $page->status) === $s)>{{ $s }}</option>@endforeach</select>
            <label for="redirect">Redirect To</label><input id="redirect" name="redirect" value="{{ old('redirect', $page->redirect) }}" placeholder="Leave blank for none, e.g. /plans.html">
            <label for="promo_code">Promo Code</label><input id="promo_code" name="promo_code" class="mono" value="{{ old('promo_code', $page->promo_code) }}" placeholder="Applied to orders that start on this page">
            <label for="market_id">Default Market</label>
            <select id="market_id" name="market_id"><option value="">--None--</option>@foreach ($markets as $m)<option value="{{ $m->id }}" @selected(old('market_id', $page->market_id) == $m->id)>{{ $m->name }}</option>@endforeach</select>
            @if ($page->exists)<label>Created / Modified</label><span class="muted">{{ $page->created_at?->format('Y-m-d H:i:s') }} · {{ $page->updated_at?->format('Y-m-d H:i:s') }}</span>@endif
        </div></div>

        <div class="panel" id="meta"><div class="panel-head"><h2>Details, Meta Data</h2></div><div class="panel-body form-grid">
            <label for="html_title">HTML Title</label><input id="html_title" name="html_title" value="{{ old('html_title', $page->html_title) }}" placeholder="Browser tab / search result title; blank uses Title">
            <label for="meta_description">Meta Description</label><input id="meta_description" name="meta_description" value="{{ old('meta_description', $page->meta_description) }}">
            <label for="meta_keywords">Meta Keywords</label><input id="meta_keywords" name="meta_keywords" value="{{ old('meta_keywords', $page->meta_keywords) }}">
            <label for="head_content">Head Content</label><textarea id="head_content" name="head_content" style="min-height:90px" placeholder="Extra meta tags, etc.">{{ old('head_content', $page->head_content) }}</textarea>
            <label for="phone">Display Phone Number</label><input id="phone" name="phone" value="{{ old('phone', $page->phone) }}" placeholder="Leave blank to use the site's">
            <label>Options</label>
            <div style="display:flex;flex-direction:column;gap:6px">
                <label class="check"><input type="hidden" name="canonical" value="0"><input type="checkbox" name="canonical" value="1" @checked(old('canonical', $page->canonical))> Canonical tag</label>
                <label class="check"><input type="hidden" name="no_index" value="0"><input type="checkbox" name="no_index" value="1" @checked(old('no_index', $page->no_index))> Disable SEO index (also leaves it out of the sitemap)</label>
            </div>
        </div></div>

        @unless ($page->file)
            <div class="panel" id="content"><div class="panel-head"><h2>Content</h2><span class="muted">HTML. Insert a content block with its code, e.g. <span class="mono">[[block|rewards-promo-band]]</span>; widgets: <span class="mono">[[zip_form]] [[plans|group=featured|limit=3]] [[phone]] [[year]]</span></span></div>
                <div class="panel-body form-grid" style="grid-template-columns:1fr">
                    <label for="page_title">Page Title (the banner heading; blank uses Title)</label><input id="page_title" name="page_title" value="{{ old('page_title', $page->page_title) }}">
                    @foreach (['content_primary' => 'Primary Content', 'content_secondary' => 'Secondary Content', 'content_parent' => 'Parent Content (shown under the banner heading, and for short blurbs)', 'content_auxiliary' => 'Auxiliary Content'] as $f => $l)
                        <label for="{{ $f }}">{{ $l }}</label><textarea id="{{ $f }}" name="{{ $f }}" spellcheck="false" @if ($f !== 'content_primary') style="min-height:120px" @endif>{{ old($f, $page->$f) }}</textarea>
                    @endforeach
                </div>
            </div>
        @endunless

        <div class="actions"><button class="btn cyan">Save Page</button><a class="btn ghost" href="{{ app_route('pages.index', ['site' => $page->site_id]) }}">Cancel</a></div>
    </form>

    @if ($page->exists)
        <div class="panel" id="components"><div class="panel-head"><h2>Page Components</h2><span class="muted">Content blocks shown on this page, by zone</span></div>
            <div class="panel-body">
                @foreach ($zones as $zone => $zoneLabel)
                    <h3>{{ $zoneLabel }}</h3>
                    @forelse ($components[$zone] ?? [] as $c)
                        <div class="row-line"><span><b>{{ $c->block->name }}</b> <span class="mono help">{{ $c->block->slug }}</span>@if ($c->block->category) <span class="help">· {{ $c->block->category->name }}</span>@endif</span>
                            <span class="actions">
                                @foreach (['up' => '↑', 'down' => '↓'] as $dir => $arrow)
                                    <form method="post" action="{{ app_route('pages.components.move', $c) }}" class="inline">@csrf<input type="hidden" name="dir" value="{{ $dir }}"><button class="btn sm ghost" aria-label="Move {{ $c->block->name }} {{ $dir }}">{{ $arrow }}</button></form>
                                @endforeach
                                @if ($blockUrl = app_route('blocks.edit', $c->block))<a class="btn sm ghost" href="{{ $blockUrl }}">Edit Block</a>@endif
                                <form method="post" action="{{ app_route('pages.components.destroy', $c) }}" class="inline">@csrf @method('delete')<button class="link-btn">[ remove ]</button></form>
                            </span></div>
                    @empty <p class="help" style="margin-bottom:10px">Nothing here.</p> @endforelse
                @endforeach
                <form method="post" action="{{ app_route('pages.components.store', $page) }}" class="form-row" style="margin-top:16px">@csrf
                    <select name="content_block_id" required aria-label="Content block" style="flex:2"><option value="">-- Add a content block --</option>
                        @foreach ($blocks->groupBy(fn ($b) => $b->category?->name ?? 'Uncategorized') as $cat => $list)
                            <optgroup label="{{ $cat }}">@foreach ($list as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach</optgroup>
                        @endforeach
                    </select>
                    <select name="zone" aria-label="Zone">@foreach ($zones as $zone => $zoneLabel)<option value="{{ $zone }}">{{ $zoneLabel }}</option>@endforeach</select>
                    <button class="btn sm">Add Component</button>
                </form>
            </div>
        </div>

        <div class="panel" id="edits"><div class="panel-head"><h2>Edits</h2></div>
            <div class="table-wrap"><table><thead><tr><th>Date</th><th>User</th><th>Change</th></tr></thead><tbody>
                @forelse ($edits as $e)
                    <tr><td>{{ $e->created_at->format('n/j/Y g:i A') }}</td><td>{{ $e->user?->name ?? 'System' }}</td><td>{{ $e->summary }}</td></tr>
                @empty <tr><td colspan="3" class="empty">No edits recorded.</td></tr> @endforelse
            </tbody></table></div></div>

        @can('delete')
            @unless ($page->file)
                <form method="post" action="{{ app_route('pages.destroy', $page) }}" data-confirm="Delete page?|This removes {{ $page->url() }} from {{ $page->site?->name }}.|Delete This Page">
                    @csrf @method('delete')<button class="btn red">Delete This Page</button>
                </form>
            @endunless
        @endcan
    @endif
@endsection
