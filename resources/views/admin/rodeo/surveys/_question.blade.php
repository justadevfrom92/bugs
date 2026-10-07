{{-- One question in the survey builder. $i is its form index, $q its values. --}}
<div class="sv-qcard">
    <div class="sv-qhead">
        <span class="num">Q</span><span class="sv-dot"></span><span class="grow"></span>
        <button type="button" class="btn sm ghost" data-move="up" aria-label="Move up">↑</button>
        <button type="button" class="btn sm ghost" data-move="down" aria-label="Move down">↓</button>
        <button type="button" class="btn sm ghost" data-move="remove">Remove</button>
    </div>
    <div class="sv-qbody">
        <input type="hidden" name="q[{{ $i }}][id]" value="{{ $q['id'] ?? '' }}" data-f="id">
        <input type="hidden" name="q[{{ $i }}][bank]" value="{{ $q['bank'] ?? '' }}" data-f="bank">
        <label class="wide">Question<input name="q[{{ $i }}][question]" value="{{ $q['question'] ?? '' }}" required maxlength="250" data-f="question" placeholder="What do you want to ask?"></label>
        <label>Category<select name="q[{{ $i }}][category]" data-f="category"><option value="">Uncategorized</option>@foreach ($categories as $c)<option value="{{ $c->id }}" @selected(($q['category'] ?? null) == $c->id)>{{ $c->name }}</option>@endforeach</select></label>
        <label>Answer type<select name="q[{{ $i }}][type]" data-f="type">@foreach (\App\Models\SurveyQuestion::TYPES as $v => $l)<option value="{{ $v }}" @selected(($q['type'] ?? '') === $v)>{{ $l }}</option>@endforeach</select></label>
        <label class="half" data-show="answers">Answers<select name="q[{{ $i }}][answer_set]" data-f="answer_set"><option value="">Write my own answers…</option>@foreach ($sets->groupBy('group') as $g => $list)<optgroup label="{{ $g }}">@foreach ($list as $s)<option value="{{ $s->id }}" @selected(($q['answer_set'] ?? null) == $s->id)>{{ $s->name }}</option>@endforeach</optgroup>@endforeach</select>
            <span class="sv-opts" data-show="chips"></span></label>
        <label class="half" data-show="custom">Your answers, one per line<textarea name="q[{{ $i }}][options]" rows="3" data-f="options">{{ $q['options'] ?? '' }}</textarea></label>
        <label class="half">Help text<input name="q[{{ $i }}][help]" value="{{ $q['help'] ?? '' }}" maxlength="200" data-f="help" placeholder="Optional hint under the question"></label>
        <label class="check"><input type="hidden" name="q[{{ $i }}][required]" value="0"><input type="checkbox" name="q[{{ $i }}][required]" value="1" @checked(! empty($q['required'])) data-f="required"> Required</label>
    </div>
</div>
