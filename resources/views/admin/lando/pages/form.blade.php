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
            <label for="meta_description">Meta Description</label><input id="meta_description" name="meta_description" value="{{ old('meta_description', $page->meta_description) }}" placeholder="Shown in search results">
        </div>
        <div class="actions" style="margin-top:20px">
            <button class="btn cyan">Save Page</button><a class="btn ghost" href="{{ route('lando.pages.index') }}">Cancel</a>
        </div>
    </div></form>

    @if ($page->exists)
        @can('delete')
            <form method="post" action="{{ route('lando.pages.destroy', $page) }}" data-confirm="Delete page?|This removes {{ $page->url() }} from the site.|Delete Page">
                @csrf @method('delete')<button class="btn red">Delete Page</button>
            </form>
        @endcan
    @endif
@endsection
