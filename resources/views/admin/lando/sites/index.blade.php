@extends('admin.layouts.app')

@section('content')
    @include('admin.partials.page-head', ['title' => 'Sites', 'sub' => 'The website this install serves. Name and address come from config/brand.php and APP_URL in .env.'])

    <div class="panel"><div class="panel-head"><h2>{{ config('brand.name') }}</h2>
        <span class="actions">
            <a class="btn sm ghost" href="{{ url('/') }}" target="_blank" rel="noopener">View Site Live</a>
            <a class="btn sm ghost" href="{{ route('lando.pages.index') }}">List Pages</a>
            <form method="post" action="{{ route('lando.pages.sitemap') }}" class="inline">@csrf<button class="btn sm">Rebuild Sitemap</button></form>
        </span></div>
        <div class="panel-body"><dl class="kv">
            <dt>Address</dt><dd class="mono">{{ config('app.url') }}</dd>
            <dt>Published Pages</dt><dd>{{ $counts['Published'] ?? 0 }}</dd>
            <dt>Draft Pages</dt><dd>{{ $counts['Draft'] ?? 0 }}</dd>
            <dt>Redirects</dt><dd>{{ $redirects }}</dd>
            <dt>Hidden From Search</dt><dd>{{ $noIndex }}</dd>
            <dt>Page Templates</dt><dd><a href="{{ route('lando.templates.index') }}">{{ $templates }}</a></dd>
            <dt>Content Blocks</dt><dd><a href="{{ route('lando.blocks.index') }}">{{ $blocks }}</a></dd>
            <dt>Sitemap</dt><dd>@if ($sitemapAt)<a href="{{ url('sitemap.xml') }}" target="_blank" rel="noopener">sitemap.xml</a> <span class="help">built {{ $sitemapAt->format('n/j/Y g:i A') }}</span>@else Not built yet @endif</dd>
        </dl></div>
    </div>
@endsection
