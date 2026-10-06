@extends('site.layouts.main')
@section('title', 'Pay Bill')
@section('crumb', 'My Account')
@section('heading', 'My Account')
@section('main')
<x-site.myaccount :c="$c" title="Pay Bill">
  <div class="co-card">
    <p>Current balance: <b>${{ number_format($c->balance, 2) }}</b>{{ $c->due_date ? ', due '.$c->due_date->format('M j, Y') : '' }}.</p>
    @if ($methods->isEmpty())
      <p class="site-alert">You don't have a saved payment method. Adding a new card online isn't available yet; please call {{ config('brand.phone') }} to pay.</p>
    @else
      <form method="post" action="{{ route('myaccount.pay.store') }}">@csrf
        <div class="form-2">
          <div class="field"><label for="amount">Amount ($)</label><input id="amount" name="amount" type="number" step="0.01" min="1" required value="{{ old('amount', $c->balance > 0 ? number_format($c->balance, 2, '.', '') : '') }}"></div>
          <div class="field"><label for="method">Pay With</label><select id="method" name="method">@foreach ($methods as $m)<option value="{{ $m->id }}" @selected($m->autopay)>{{ $m->label() }}</option>@endforeach</select></div>
        </div>
        <button class="btn" style="margin-top:20px">Pay Now</button>
      </form>
    @endif
  </div>
</x-site.myaccount>
@endsection
