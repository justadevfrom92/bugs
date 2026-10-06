@extends('site.layouts.main')
@section('title', $survey->title)
@section('crumb', 'Survey')
@section('heading', $survey->title)
@section('banner')@if ($survey->intro)<p>{{ $survey->intro }}</p>@endif @endsection
@section('main')
<section class="section"><div class="wrap narrow">
  <div class="co-card">
  @if (session('thanks'))
    <div class="site-alert ok" role="status">Thank you — your answers were received.</div>
    <p><a class="btn btn-outline" href="{{ url('/') }}">Back to the homepage</a></p>
  @elseif ($survey->status !== 'active')
    <p>This survey is closed. Thank you for your interest.</p>
  @else
    @if ($errors->any())<div class="site-alert error" role="alert">{{ $errors->first() }}</div>@endif
    {{-- The signed link's query string is kept so the answer stays tied to the customer the email was sent to --}}
    <form method="post" action="{{ route('survey.store', $survey).($query ? '?'.http_build_query($query) : '') }}">@csrf
      @if ($customer)<p class="muted-text">Answering as {{ $customer->first_name }} (account ending {{ substr($customer->account, -4) }}).</p>@endif
      @foreach ($survey->questions->values() as $i => $q)
        <fieldset class="field" style="margin-top:20px">
          <legend style="font-weight:700;color:var(--navy);margin-bottom:8px">{{ $i + 1 }}. {{ $q->question }}</legend>
          @if ($q->type === 'rating')
            <div class="rating-row">@for ($n = 0; $n <= 10; $n++)<label class="radio"><input type="radio" name="a[{{ $i }}]" value="{{ $n }}" @checked(old("a.$i") === (string) $n)> {{ $n }}</label>@endfor</div>
            <p class="muted-text" style="display:flex;justify-content:space-between;margin:0"><span>Not likely</span><span>Very likely</span></p>
          @elseif ($q->type === 'choice')
            @foreach ($q->options ?? [] as $opt)<label class="radio"><input type="radio" name="a[{{ $i }}]" value="{{ $opt }}" @checked(old("a.$i") === $opt)> {{ $opt }}</label>@endforeach
          @else
            <textarea name="a[{{ $i }}]" aria-label="{{ $q->question }}">{{ old("a.$i") }}</textarea>
          @endif
        </fieldset>
      @endforeach
      <button class="btn" style="margin-top:20px">Submit</button>
    </form>
  @endif
  </div>
</div></section>
@endsection
