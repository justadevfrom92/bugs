@extends('admin.layouts.app')

@section('content')
    @include('admin.partials.page-head', [
        'title' => 'Pages for '.config('brand.name'),
        'sub' => 'Working on site: '.e(config('brand.domain')),
        'actions' => '<form method="post" action="'.route('lando.pages.sitemap').'" class="inline">'.csrf_field().'<button class="btn ghost">Rebuild Sitemap</button></form>'
            .'<a class="btn" href="'.route('lando.pages.create').'">Add a Page</a>',
    ])
    <div class="panel"><div class="panel-head"><h2>Site Structure</h2><span class="muted">{{ $count }} pages</span></div>
        <div class="panel-body">@include('admin.lando.pages._tree', ['nodes' => $tree])</div>
    </div>
@endsection
