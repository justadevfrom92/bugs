@extends('admin.layouts.app')

@section('content')
    @include('admin.partials.page-head', [
        'title' => 'Pages for '.$site->name,
        'sub' => 'Working on site: <span class="mono">'.e($site->domain).'</span>',
        'actions' => (($sitemap = app_route('pages.sitemap')) ? '<form method="post" action="'.$sitemap.'" class="inline">'.csrf_field().'<button class="btn ghost">Rebuild Sitemap</button></form>' : '')
            .'<a class="btn" href="'.app_route('pages.create', ['site' => $site->id]).'">Add a Page</a>',
    ])
    @if ($sites->count() > 1)
        <form method="get" class="form-row" style="margin-bottom:16px">
            <label class="muted" for="site">Site</label>
            <select id="site" name="site" data-autosubmit>@foreach ($sites as $s)<option value="{{ $s->id }}" @selected($s->id === $site->id)>{{ $s->name }} ({{ $s->domain }})</option>@endforeach</select>
        </form>
    @endif
    <div class="panel"><div class="panel-head"><h2>Site Structure</h2><span class="muted">{{ $count }} pages</span></div>
        <div class="panel-body">@include('admin.lando.pages._tree', ['nodes' => $tree])</div>
    </div>
@endsection
