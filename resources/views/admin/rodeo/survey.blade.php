@extends('admin.layouts.app')

@section('crumb', $survey->exists ? $survey->title : 'New Survey')

@section('content')
    @include('admin.partials.page-head', ['title' => $survey->exists ? $survey->title : 'New Survey', 'sub' => $survey->exists ? 'Customers answer at <span class="mono">'.e(url('survey/'.$survey->slug)).'</span>' : 'Ratings are 0–10 (shown as a Net Promoter Score in results).',
        'actions' => $survey->exists ? '<a class="btn ghost" href="'.route('rodeo.surveys.results', $survey).'">Results</a>' : null])
    @php $qs = old('q', $questions->map(fn ($q) => ['question' => $q->question, 'type' => $q->type, 'options' => $q->type === 'choice' ? implode("\n", $q->options ?? []) : ''])->all()) ?: [['question' => '', 'type' => 'rating', 'options' => '']]; @endphp
    <form method="post" action="{{ $survey->exists ? route('rodeo.surveys.update', $survey) : route('rodeo.surveys.store') }}" style="display:flex;flex-direction:column;gap:20px">@csrf @if ($survey->exists) @method('put') @endif
        <div class="panel"><div class="panel-body form-grid">
            <label for="title">Title</label><input id="title" name="title" required value="{{ old('title', $survey->title) }}">
            <label for="intro">Intro</label><input id="intro" name="intro" value="{{ old('intro', $survey->intro) }}">
            <label for="status">Status</label><select id="status" name="status">@foreach (['active' => 'Active (taking responses)', 'closed' => 'Closed'] as $v => $l)<option value="{{ $v }}" @selected(old('status', $survey->status) === $v)>{{ $l }}</option>@endforeach</select>
        </div></div>
        <div class="panel"><div class="panel-head"><h2>Questions</h2><button type="button" class="btn sm ghost" data-clone="#q-row" data-into="#q-rows">Add a Question</button></div>
            <div class="table-wrap"><table><thead><tr><th>Question</th><th>Type</th><th>Choices (one per line)</th><th></th></tr></thead>
            <tbody id="q-rows">@foreach ($qs as $i => $q)
                <tr><td><input class="cell-input" name="q[{{ $i }}][question]" value="{{ $q['question'] }}" required aria-label="Question"></td>
                    <td><select name="q[{{ $i }}][type]" aria-label="Type">@foreach (['rating' => 'Rating 0–10', 'choice' => 'Multiple choice', 'text' => 'Text answer'] as $v => $l)<option value="{{ $v }}" @selected($q['type'] === $v)>{{ $l }}</option>@endforeach</select></td>
                    <td><textarea name="q[{{ $i }}][options]" aria-label="Choices" style="min-height:60px;width:100%">{{ $q['options'] }}</textarea></td>
                    <td><button type="button" class="btn sm ghost" data-remove-row>Remove</button></td></tr>
            @endforeach</tbody></table></div>
        </div>
        <div><button class="btn cyan">Save Survey</button></div>
    </form>
    <template id="q-row"><tr><td><input class="cell-input" name="q[__i__][question]" required aria-label="Question"></td>
        <td><select name="q[__i__][type]" aria-label="Type"><option value="rating">Rating 0–10</option><option value="choice">Multiple choice</option><option value="text">Text answer</option></select></td>
        <td><textarea name="q[__i__][options]" aria-label="Choices" style="min-height:60px;width:100%"></textarea></td><td><button type="button" class="btn sm ghost" data-remove-row>Remove</button></td></tr></template>
@endsection
