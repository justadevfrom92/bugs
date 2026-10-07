@extends('admin.layouts.app')

@section('content')
    @include('admin.partials.page-head', ['title' => 'Answer Sets',
        'sub' => 'Reusable lists of answers, grouped by kind. Scales are ordered and scored 1 to n in results. Changing a set updates the questions that use it.'])

    <div class="sv-builder">
        <div style="min-width:0">
            @forelse ($groups as $group => $list)
                <div class="sv-cat-head"><h2>{{ $group }}</h2><span class="muted">{{ $list->count() }} {{ Str::plural('set', $list->count()) }}</span></div>
                <div class="sv-grid">
                    @foreach ($list as $s)
                        <div class="panel">
                            <div class="panel-head"><h3 style="margin:0">{{ $s->name }}</h3><span class="sv-type">{{ \App\Models\SurveyAnswerSet::TYPES[$s->type] ?? $s->type }}</span></div>
                            <div class="panel-body" style="display:grid;gap:10px">
                                @if ($s->type === 'scale')
                                    <ol style="margin:0 0 0 18px;font-size:.88rem">@foreach ($s->options as $o)<li>{{ $o }} <span class="muted">· scores {{ $loop->iteration }}</span></li>@endforeach</ol>
                                @else
                                    <div class="sv-opts">@foreach ($s->options as $o)<span class="sv-opt">{{ $o }}</span>@endforeach</div>
                                @endif
                                <div class="muted" style="font-size:.8rem">Used by {{ $s->questions_count }} survey {{ Str::plural('question', $s->questions_count) }} and {{ $s->bank_questions_count }} in the bank</div>
                                <div class="actions"><a class="btn sm ghost" href="{{ route('rodeo.surveys.answers', ['edit' => $s->id]) }}">Edit</a>
                                    @if (! $s->questions_count && ! $s->bank_questions_count)<form method="post" action="{{ route('rodeo.surveys.answers.destroy', $s) }}" class="inline" data-confirm="Delete this answer set?|Nothing uses it.|Delete">@csrf @method('delete')<button class="btn sm ghost">Delete</button></form>@endif</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @empty
                <div class="panel"><div class="panel-body empty">No answer sets yet.</div></div>
            @endforelse
        </div>

        <aside class="sv-side">
            <form method="post" action="{{ $edit ? route('rodeo.surveys.answers.update', $edit) : route('rodeo.surveys.answers.store') }}" class="panel">@csrf @if ($edit) @method('put') @endif
                <div class="panel-head"><h3 style="margin:0">{{ $edit ? 'Edit Answer Set' : 'New Answer Set' }}</h3>@if ($edit)<a class="help" href="{{ route('rodeo.surveys.answers') }}">Cancel</a>@endif</div>
                <div class="panel-body sv-qbody" style="grid-template-columns:1fr">
                    <label>Name<input name="name" required maxlength="80" value="{{ old('name', $edit?->name) }}" placeholder="e.g. Satisfaction (5-point)"></label>
                    <label>Kind<input name="group" required maxlength="40" list="sv-groups" value="{{ old('group', $edit?->group) }}" placeholder="Satisfaction, Agreement…"></label>
                    <datalist id="sv-groups">@foreach (\App\Models\SurveyAnswerSet::GROUPS as $g)<option value="{{ $g }}">@endforeach</datalist>
                    <label>Type<select name="type">@foreach (\App\Models\SurveyAnswerSet::TYPES as $v => $l)<option value="{{ $v }}" @selected(old('type', $edit?->type ?? 'scale') === $v)>{{ $l }}</option>@endforeach</select></label>
                    <label>Answers, one per line<textarea name="options" rows="6" required>{{ old('options', $edit ? implode("\n", $edit->options) : '') }}</textarea></label>
                    <p class="help" style="margin:0">For a scale, list answers from lowest to highest.</p>
                    <button class="btn cyan">{{ $edit ? 'Save Answer Set' : 'Add Answer Set' }}</button>
                </div>
            </form>
        </aside>
    </div>
@endsection
