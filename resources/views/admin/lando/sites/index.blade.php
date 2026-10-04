@extends('admin.layouts.app')

@section('content')
    @include('admin.partials.page-head', ['title' => 'Sites', 'sub' => 'Websites served by this install. Each answers on its own domain and has its own pages; content blocks can be shared by every site.',
        'actions' => '<a class="btn cyan" href="'.route('lando.sites.create').'">New Site</a>'])

    <div class="panel"><div class="table-wrap"><table>
        <thead><tr><th>Site</th><th>Domain</th><th class="num">Pages</th><th class="num">Published</th><th class="num">Own Blocks</th><th>Status</th><th></th></tr></thead>
        <tbody>@foreach ($sites as $s)
            <tr><td><b>{{ $s->name }}</b></td><td class="mono">{{ $s->domain }}</td><td class="num">{{ $s->pages_count }}</td><td class="num">{{ $s->published_count }}</td><td class="num">{{ $s->blocks_count }}</td>
                <td>@include('admin.partials.pill', ['text' => $s->status, 'tone' => $s->status === 'Active' ? 'ok' : ''])</td>
                <td class="actions"><a class="btn sm ghost" href="{{ route('lando.pages.index', ['site' => $s->id]) }}">List Pages</a><a class="btn sm ghost" href="{{ route('lando.sites.edit', $s) }}">Edit Site</a></td></tr>
        @endforeach</tbody>
    </table></div></div>

    <div class="panel"><div class="panel-body form-row" style="align-items:center">
        <span>Sitemap for the main site: @if ($sitemapAt)<a href="{{ url('sitemap.xml') }}" target="_blank" rel="noopener">sitemap.xml</a> <span class="help">built {{ $sitemapAt->format('n/j/Y g:i A') }}</span>@else not built yet @endif</span>
        <form method="post" action="{{ route('lando.pages.sitemap') }}" class="inline">@csrf<button class="btn sm">Rebuild Sitemap</button></form>
    </div></div>
@endsection
