{{-- A website page built in Lando. Header, footer and styling are the same as the built-in pages. --}}
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ $page->html_title ?: $page->title.' | '.$site->name }}</title>
  @if ($page->meta_description)<meta name="description" content="{{ $page->meta_description }}">@endif
  @if ($page->meta_keywords)<meta name="keywords" content="{{ $page->meta_keywords }}">@endif
  @if ($page->no_index || $page->status !== 'Published')<meta name="robots" content="noindex">@endif
  @if ($page->canonical)<link rel="canonical" href="{{ $page->liveUrl() }}">@endif
  @if ($hasAmp)<link rel="amphtml" href="{{ url('amp/'.ltrim($page->path, '/')) }}">@endif
  <link rel="stylesheet" href="/css/style.css">
  {!! $page->head_content !!}
  <script src="/shared/boot.js" data-then="/js/main.js"></script>
</head>
<body data-page="{{ $page->path }}" @if ($page->promo_code) data-promo="{{ $page->promo_code }}" @endif @if ($page->rep_id) data-rep="{{ $page->rep_id }}" @endif @if ($page->market) data-market="{{ $page->market->name }}" @endif>
  <div id="site-header"></div>
  @if ($page->status !== 'Published')<div style="background:#fff3d6;color:#7a5000;text-align:center;padding:8px;font-weight:600">Draft — only signed-in admin users can see this page.</div>@endif

  <main>
    <section class="page-banner">
      <div class="wrap">
        <div class="crumbs"><a href="/">Home</a> / {{ $page->title }}</div>
        @if ($page->market_label)<p class="eyebrow">{{ $page->market_label }}</p>@endif
        <h1>{{ $title }}</h1>
        @if ($content['parent'])<div class="banner-sub">{!! $content['parent'] !!}</div>@endif
        @if ($page->phone ?: $site->phone)<p>Call <a href="tel:{{ preg_replace('/\D/', '', $page->phone ?: $site->phone) }}">{{ $page->phone ?: $site->phone }}</a></p>@endif
      </div>
    </section>

    @if ($zones['top'])<section class="section cms-zone" data-zone="top"><div class="wrap">{!! $zones['top'] !!}</div></section>@endif

    <section class="section">
      <div class="wrap" @if ($zones['sidebar']) style="display:grid;grid-template-columns:minmax(0,1fr) 320px;gap:40px;align-items:start" @endif>
        <div class="cms-content">
          {!! $content['primary'] !!}
          {!! $content['secondary'] !!}
          {!! $content['auxiliary'] !!}
          @if ($grid)@include('site.price-grid', ['grid' => $grid, 'label' => $page->market_label])@endif
          @if ($zones['main'])<div class="cms-zone" data-zone="main">{!! $zones['main'] !!}</div>@endif
        </div>
        @if ($zones['sidebar'])<aside class="cms-zone" data-zone="sidebar">{!! $zones['sidebar'] !!}</aside>@endif
      </div>
    </section>

    @if ($zones['bottom'])<section class="section cms-zone" data-zone="bottom"><div class="wrap">{!! $zones['bottom'] !!}</div></section>@endif
  </main>

  <div id="site-footer"></div>
</body>
</html>
