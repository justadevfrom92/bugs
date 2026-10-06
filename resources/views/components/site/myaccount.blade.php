@props(['c', 'title'])
{{-- My Account frame: account menu on the left, the page on the right --}}
@php
    $menu = [
        'Account' => [['Dashboard', 'myaccount.dashboard', []], ['Profile & Preferences', 'myaccount.profile', []], ['Message Center', 'myaccount.messages', []]],
        'Bill & Payments' => [['View Bills', 'myaccount.bills', []], ['Pay Bill', 'myaccount.pay', []], ['Payment Methods', 'myaccount.methods', []],
            ['AutoPay', 'myaccount.product', ['autopay']], ['Paperless Billing', 'myaccount.product', ['paperless-billing']], ['Pick Your Due Date', 'myaccount.product', ['giddy-up']]],
        'Plan & Services' => [['Current Plan & Renew', 'myaccount.plan', []], ['Transfer Service', 'myaccount.transfer', []], ['Peak Perks', 'myaccount.product', ['peak-perks']], ['Energy Insights', 'myaccount.insights', []]],
        'Rewards' => [['My Rewards', 'myaccount.rewards', []], ['Refer a Friend', 'myaccount.refer', []]],
    ];
    $here = url()->current();
@endphp
<section class="section ma">
  <div class="wrap ma-grid">
    <nav class="ma-nav" aria-label="My Account">
      <div class="ma-who"><b>{{ $c->name }}</b><span>Account {{ $c->account }}</span></div>
      @foreach ($menu as $heading => $items)
        <h4>{{ $heading }}</h4>
        @foreach ($items as [$label, $route, $params])
          @php $url = route($route, $params); @endphp
          <a href="{{ $url }}" @class(['on' => $url === $here]) @if ($url === $here) aria-current="page" @endif>{{ $label }}</a>
        @endforeach
      @endforeach
      <form method="post" action="{{ route('myaccount.logout') }}">@csrf<button class="ma-out"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 4h4v16h-4M10 8l-4 4 4 4M6 12h11"/></svg>Sign Out</button></form>
    </nav>
    <div class="ma-main">
      <h2 class="ma-title">{{ $title }}</h2>
      @if (session('status'))<div class="site-alert ok" role="status">{{ session('status') }}</div>@endif
      @if ($errors->any())<div class="site-alert error" role="alert">{{ $errors->first() }}</div>@endif
      {{ $slot }}
    </div>
  </div>
</section>
