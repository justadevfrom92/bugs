@extends('admin.layouts.app')

@section('crumb', $survey->title.' Results')

@section('content')
    @include('admin.partials.page-head', ['title' => $survey->title, 'sub' => number_format($responses->count()).' responses · '.ucfirst($survey->status),
        'actions' => '<a class="btn ghost" href="'.route('rodeo.surveys.edit', $survey).'">Edit Survey</a>'])
    @foreach ($results as $r)
        <div class="panel"><div class="panel-head"><h2>{{ $r['q']->question }}</h2><span class="muted">{{ $r['count'] }} answers</span></div><div class="panel-body">
            @if ($r['q']->type === 'rating')
                <p>Average <b>{{ $r['avg'] ?? '—' }}</b> / 10 · Net Promoter Score <b>{{ $r['nps'] ?? '—' }}</b></p>
            @elseif ($r['q']->type === 'choice')
                @foreach ($r['choices'] as $choice => $n)
                    <div class="row-line"><span>{{ $choice }}</span><span>{{ $n }} ({{ round(100 * $n / max(1, $r['count'])) }}%)</span></div>
                @endforeach
            @else
                @forelse ($r['texts'] as $t)<p class="note" style="margin-bottom:8px">{{ $t }}</p>@empty<p class="muted">No written answers.</p>@endforelse
            @endif
        </div></div>
    @endforeach
@endsection
