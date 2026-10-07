@extends('admin.layouts.app')

@section('content')
    @include('admin.partials.page-head', ['title' => 'Question Bank',
        'sub' => 'Ready-made questions, filed by category, to add to any survey from the survey builder. A survey keeps its own copy, so editing the bank never changes past surveys.'])

    <div class="sv-builder">
        <div style="min-width:0">
            <div class="actions" style="margin-bottom:14px"><a class="btn sm {{ $only ? 'ghost' : '' }}" href="{{ route('rodeo.surveys.bank') }}">All</a>@foreach ($allCategories as $c)<a class="btn sm {{ (string) $only === (string) $c->id ? '' : 'ghost' }}" href="{{ route('rodeo.surveys.bank', ['category' => $c->id]) }}"><span class="sv-dot" style="--dot:{{ $c->color }};margin-right:6px"></span>{{ $c->name }}</a>@endforeach</div>
            @foreach ($categories as $cat)
                @continue($only && (string) $only !== (string) $cat->id)
                @include('admin.rodeo.surveys._bank-group', ['title' => $cat->name, 'desc' => $cat->description, 'dot' => $cat->color, 'items' => $cat->bankQuestions])
            @endforeach
            @if (! $only && $uncategorized->isNotEmpty())@include('admin.rodeo.surveys._bank-group', ['title' => 'Uncategorized', 'desc' => null, 'dot' => '#96999d', 'items' => $uncategorized])@endif
        </div>

        <aside class="sv-side">
            <form method="post" action="{{ $edit ? route('rodeo.surveys.bank.update', $edit) : route('rodeo.surveys.bank.store') }}" class="panel">@csrf @if ($edit) @method('put') @endif
                <div class="panel-head"><h3 style="margin:0">{{ $edit ? 'Edit Question' : 'Add a Question' }}</h3>@if ($edit)<a class="help" href="{{ route('rodeo.surveys.bank') }}">Cancel</a>@endif</div>
                <div class="panel-body sv-qbody" style="grid-template-columns:1fr">
                    <label>Question<textarea name="question" rows="3" required maxlength="250">{{ old('question', $edit?->question) }}</textarea></label>
                    <label>Category<select name="survey_category_id"><option value="">Uncategorized</option>@foreach ($allCategories as $c)<option value="{{ $c->id }}" @selected(old('survey_category_id', $edit?->survey_category_id ?? $only) == $c->id)>{{ $c->name }}</option>@endforeach</select></label>
                    <label>Answer type<select name="type" id="bk-type">@foreach (\App\Models\SurveyQuestion::TYPES as $v => $l)<option value="{{ $v }}" @selected(old('type', $edit?->type ?? 'scale') === $v)>{{ $l }}</option>@endforeach</select></label>
                    <label id="bk-set">Answer set<select name="survey_answer_set_id"><option value="">Choose…</option>@foreach ($sets->groupBy('group') as $g => $list)<optgroup label="{{ $g }}">@foreach ($list as $s)<option value="{{ $s->id }}" @selected(old('survey_answer_set_id', $edit?->survey_answer_set_id) == $s->id)>{{ $s->name }}</option>@endforeach</optgroup>@endforeach</select>
                        <a class="help" href="{{ route('rodeo.surveys.answers') }}">Manage answer sets</a></label>
                    <label>Help text<input name="help" maxlength="200" value="{{ old('help', $edit?->help) }}" placeholder="Optional hint under the question"></label>
                    <button class="btn cyan">{{ $edit ? 'Save Question' : 'Add to Bank' }}</button>
                </div>
            </form>
        </aside>
    </div>
    <script>
    (function () {
        var t = document.getElementById('bk-type'), set = document.getElementById('bk-set');
        function sync() { set.hidden = ['scale', 'single', 'multi'].indexOf(t.value) < 0; }
        t.addEventListener('change', sync); sync();
    })();
    </script>
@endsection
