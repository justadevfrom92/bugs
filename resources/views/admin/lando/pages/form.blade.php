@extends('admin.layouts.app')

@section('crumb', $page->exists ? 'Edit Page' : 'Add a Page')

@section('content')
    @include('admin.partials.page-head', ['title' => $page->exists ? 'Editing '.$page->title : 'Add a Page', 'sub' => $page->exists ? e($page->url()) : null])

    <form method="post" action="{{ $page->exists ? route('lando.pages.update', $page) : route('lando.pages.store') }}" class="panel"><div class="panel-body">
        @csrf @if ($page->exists) @method('put') @endif
        <div class="form-grid">
            <label for="title">Title</label><input id="title" name="title" required value="{{ old('title', $page->title) }}">
            <label for="path">URL Path</label><input id="path" name="path" required class="mono" value="{{ old('path', $page->path) }}" placeholder="get-to-learnin/new-article">
            <label for="template_id">Template</label>
            <select id="template_id" name="template_id"><option value="">—</option>@foreach ($templates as $t)<option value="{{ $t->id }}" @selected(old('template_id', $page->template_id) == $t->id)>{{ $t->name }}</option>@endforeach</select>
            <label for="redirect">Redirect To</label><input id="redirect" name="redirect" value="{{ old('redirect', $page->redirect) }}" placeholder="Leave blank for none">
            <label for="status">Status</label>
            <select id="status" name="status">@foreach (['Published', 'Draft'] as $s)<option @selected(old('status', $page->status) === $s)>{{ $s }}</option>@endforeach</select>
            <label for="html_title">HTML Title</label><input id="html_title" name="html_title" value="{{ old('html_title', $page->html_title) }}" placeholder="Browser tab / search result title; blank uses Title">
            <label for="meta_description">Meta Description</label><input id="meta_description" name="meta_description" value="{{ old('meta_description', $page->meta_description) }}" placeholder="Shown in search results">
            <label for="meta_keywords">Meta Keywords</label><input id="meta_keywords" name="meta_keywords" value="{{ old('meta_keywords', $page->meta_keywords) }}">
            <label for="promo_code">Promo Code</label><input id="promo_code" name="promo_code" class="mono" value="{{ old('promo_code', $page->promo_code) }}" placeholder="Applied to orders that start on this page">
            <label for="no_index">Disable SEO Index</label>
            <label class="check"><input type="hidden" name="no_index" value="0"><input id="no_index" type="checkbox" name="no_index" value="1" @checked(old('no_index', $page->no_index))> Keep this page out of search engines and the sitemap</label>
        </div>
        <div class="actions" style="margin-top:20px">
            <button class="btn cyan">Save Page</button><a class="btn ghost" href="{{ route('lando.pages.index') }}">Cancel</a>
        </div>
    </div></form>

    @if ($page->exists)
        <div class="panel"><div class="panel-head"><h2>Edits</h2><span class="muted">Created {{ $page->created_at?->format('n/j/Y g:i A') }} · Modified {{ $page->updated_at?->format('n/j/Y g:i A') }}</span></div>
            <div class="table-wrap"><table><thead><tr><th>Date</th><th>User</th><th>Change</th></tr></thead><tbody>
                @forelse ($edits as $e)
                    <tr><td>{{ $e->created_at->format('n/j/Y g:i A') }}</td><td>{{ $e->user?->name ?? 'System' }}</td><td>{{ $e->summary }}</td></tr>
                @empty <tr><td colspan="3" class="empty">No edits recorded.</td></tr> @endforelse
            </tbody></table></div></div>
        @can('delete')
            <form method="post" action="{{ route('lando.pages.destroy', $page) }}" data-confirm="Delete page?|This removes {{ $page->url() }} from the site.|Delete Page">
                @csrf @method('delete')<button class="btn red">Delete Page</button>
            </form>
        @endcan
    @endif
@endsection
