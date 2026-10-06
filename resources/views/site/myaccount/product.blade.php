@extends('site.layouts.main')
@section('title', $p[1])
@section('crumb', 'My Account')
@section('heading', 'My Account')
@section('main')
<x-site.myaccount :c="$c" :title="$p[1]">
  <div class="co-card">
    <p>{{ $p[2] }}</p>
    <p>Status: <b>{{ $on ? 'Enrolled' : 'Not enrolled' }}</b></p>
    @if ($slug === 'autopay' && ! $hasMethod && ! $on)<p class="site-alert">Add a payment method first (<a href="{{ route('myaccount.methods') }}">Payment Methods</a>).</p>@endif
    <form method="post" action="{{ route('myaccount.product.toggle', $slug) }}">@csrf
      <input type="hidden" name="enroll" value="{{ $on ? 0 : 1 }}">
      <button class="btn {{ $on ? 'btn-outline' : '' }}">{{ $on ? 'Un-enroll' : 'Enroll' }}</button>
    </form>
  </div>
</x-site.myaccount>
@endsection
