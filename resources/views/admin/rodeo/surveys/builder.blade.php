@extends('admin.layouts.app')

@section('crumb', $survey->exists ? $survey->title : 'New Survey')

@section('content')
    @php
        $rows = old('q') ? array_values(old('q')) : $questions->map(fn ($q) => ['id' => $q->id, 'bank' => $q->survey_bank_question_id, 'category' => $q->survey_category_id, 'question' => $q->question,
            'type' => $q->type, 'answer_set' => $q->survey_answer_set_id, 'options' => $q->survey_answer_set_id ? '' : implode("\n", $q->options ?? []), 'required' => $q->required, 'help' => $q->help])->all();
        $colors = $categories->pluck('color', 'id');
        $actions = $survey->exists
            ? '<a class="btn ghost" href="'.route('rodeo.surveys.results', $survey).'">Results</a><a class="btn ghost" href="'.url('survey/'.$survey->slug).'" target="_blank" rel="noopener">Open Survey</a>'
              .'<form method="post" action="'.route('rodeo.surveys.copy', $survey).'" class="inline">'.csrf_field().'<button class="btn ghost">Copy</button></form>'
            : null;
    @endphp
    @include('admin.partials.page-head', ['title' => $survey->exists ? $survey->title : 'New Survey',
        'sub' => $survey->exists ? 'Customers answer at <span class="mono">'.e(url('survey/'.$survey->slug)).'</span> · '.number_format($responses).' '.Str::plural('response', $responses) : 'Build it from the Question Bank or write your own questions; file each under a category.',
        'actions' => $actions])

    <form method="post" action="{{ $survey->exists ? route('rodeo.surveys.update', $survey) : route('rodeo.surveys.store') }}" id="sv-form">@csrf @if ($survey->exists) @method('put') @endif
    <div class="sv-builder">
        <div style="display:grid;grid-template-columns:minmax(0, 1fr);gap:20px;min-width:0">
            <div class="panel">
                <div class="panel-head"><h2>Details</h2>@if ($survey->exists)@include('admin.partials.pill', ['text' => $survey->stateLabel(), 'tone' => $survey->isOpen() ? 'ok' : ''])@endif</div>
                <div class="panel-body form-grid">
                    <label for="title">Title</label><input id="title" name="title" required value="{{ old('title', $survey->title) }}">
                    <label for="intro">Intro</label><textarea id="intro" name="intro" maxlength="1000" style="min-height:60px;font-family:inherit" placeholder="Shown at the top of the survey">{{ old('intro', $survey->intro) }}</textarea>
                    <label for="thank_you">Thank-you message</label><textarea id="thank_you" name="thank_you" maxlength="1000" style="min-height:60px;font-family:inherit" placeholder="Shown after someone submits">{{ old('thank_you', $survey->thank_you) }}</textarea>
                    <label for="status">Status</label><select id="status" name="status">@foreach (['active' => 'Active (taking responses between the dates below)', 'closed' => 'Closed'] as $v => $l)<option value="{{ $v }}" @selected(old('status', $survey->status) === $v)>{{ $l }}</option>@endforeach</select>
                    <label for="opens_on">Opens</label><input id="opens_on" name="opens_on" type="date" value="{{ old('opens_on', $survey->opens_on?->toDateString()) }}" style="max-width:20ch">
                    <label for="closes_on">Closes</label><input id="closes_on" name="closes_on" type="date" value="{{ old('closes_on', $survey->closes_on?->toDateString()) }}" style="max-width:20ch">
                    <span></span><p class="help" style="margin:0">Leave the dates empty to keep it open until you close it.</p>
                </div>
            </div>

            <div class="panel">
                <div class="panel-head"><h2>Questions</h2><span class="muted" id="sv-count"></span></div>
                <div class="panel-body">
                    @if ($responses)<div class="banner-note" style="margin-bottom:12px">This survey has {{ $responses }} {{ Str::plural('response', $responses) }}. Rewording and reordering is safe; changing a question's type or answers changes how its past answers read in results.</div>@endif
                    <div id="sv-rows">@foreach ($rows as $i => $q)@include('admin.rodeo.surveys._question', ['i' => $i, 'q' => $q])@endforeach</div>
                    <p class="empty" id="sv-empty" @if ($rows) hidden @endif>No questions yet. Add some from the Question Bank on the right, or write your own.</p>
                    <button type="button" class="btn ghost" id="sv-add" style="margin-top:12px">+ Write a Question</button>
                </div>
            </div>
        </div>

        <aside class="sv-side" style="display:grid;grid-template-columns:minmax(0, 1fr);gap:16px">
            <div class="panel"><div class="panel-body" style="display:grid;gap:10px">
                <button class="btn cyan">Save Survey</button>
                @if ($survey->exists && ! $responses)<button type="submit" form="sv-delete" class="btn ghost red-text">Delete Survey</button>@endif
            </div></div>
            <div class="panel">
                <div class="panel-head"><h3 style="margin:0">From the Question Bank</h3></div>
                <div class="panel-body" style="display:grid;gap:8px">
                    <label class="muted" for="sv-bank" style="font-size:.8rem">Pick a ready-made question</label>
                    <select id="sv-bank"><option value="">Choose…</option>
                        @foreach ($bank->groupBy(fn ($b) => $b->category?->name ?? 'Uncategorized') as $cat => $items)<optgroup label="{{ $cat }}">@foreach ($items as $b)<option value="{{ $b->id }}">{{ Str::limit($b->question, 70) }}</option>@endforeach</optgroup>@endforeach
                    </select>
                    <button type="button" class="btn sm" id="sv-bank-add">Add to Survey</button>
                    <a class="help" href="{{ route('rodeo.surveys.bank') }}">Manage the Question Bank</a>
                </div>
            </div>
            <div class="panel">
                <div class="panel-head"><h3 style="margin:0">Outline</h3></div>
                <div class="panel-body"><ul class="sv-outline" id="sv-outline"></ul></div>
            </div>
        </aside>
    </div>
    </form>
    @if ($survey->exists && ! $responses)<form method="post" action="{{ route('rodeo.surveys.destroy', $survey) }}" id="sv-delete" data-confirm="Delete this survey?|It has no responses, so nothing else is lost.|Delete">@csrf @method('delete')</form>@endif

    <template id="sv-row">@include('admin.rodeo.surveys._question', ['i' => '__i__', 'q' => ['type' => 'scale', 'required' => false]])</template>

    <script>
    (function () {
        var bank = @json($bankJson), sets = @json($sets->mapWithKeys(fn ($s) => [$s->id => ['type' => $s->type, 'options' => $s->options]])),
            colors = @json($colors), names = @json($categories->pluck('name', 'id')), listed = ['scale', 'single', 'multi'];
        var box = document.getElementById('sv-rows'), next = {{ count($rows) }};

        function cards() { return [].slice.call(box.querySelectorAll('.sv-qcard')); }
        function sync(card) {
            var type = card.querySelector('[data-f=type]').value, set = card.querySelector('[data-f=answer_set]'), cat = card.querySelector('[data-f=category]').value;
            card.querySelector('[data-show=answers]').hidden = listed.indexOf(type) < 0;
            card.querySelector('[data-show=custom]').hidden = listed.indexOf(type) < 0 || !!set.value;
            [].slice.call(set.options).forEach(function (o) { if (o.value) o.hidden = sets[o.value] && (type === 'multi') !== (sets[o.value].type === 'multi'); });
            var chips = card.querySelector('[data-show=chips]');
            chips.innerHTML = set.value && sets[set.value] ? sets[set.value].options.map(function (o) { return '<span class="sv-opt">' + o.replace(/[&<>"]/g, '') + '</span>'; }).join('') : '';
            card.style.setProperty('--dot', colors[cat] || '#D5D9DA');
            card.querySelector('.grow').textContent = card.querySelector('[data-f=question]').value || 'New question';
        }
        function refresh() {
            var list = cards();
            list.forEach(function (c, n) { c.querySelector('.num').textContent = 'Q' + (n + 1); sync(c); });
            document.getElementById('sv-empty').hidden = list.length > 0;
            document.getElementById('sv-count').textContent = list.length + (list.length === 1 ? ' question' : ' questions') + ' · ' + list.filter(function (c) { return c.querySelector('[data-f=required]').checked; }).length + ' required';
            // Outline: categories in the order they first appear, with their question numbers
            var groups = [], seen = {};
            list.forEach(function (c, n) {
                var id = c.querySelector('[data-f=category]').value || '0';
                if (!seen[id]) { seen[id] = { id: id, nums: [] }; groups.push(seen[id]); }
                seen[id].nums.push(n + 1);
            });
            document.getElementById('sv-outline').innerHTML = groups.map(function (g) {
                return '<li><span class="sv-dot" style="--dot:' + (colors[g.id] || '#96999d') + '"></span><span>' + (names[g.id] || 'Uncategorized') + '</span><span>Q' + g.nums.join(', Q') + '</span></li>';
            }).join('') || '<li class="muted">Nothing yet</li>';
        }
        function add(values) {
            var html = document.getElementById('sv-row').innerHTML.replace(/__i__/g, 'n' + (next++));
            box.insertAdjacentHTML('beforeend', html);
            var card = box.lastElementChild;
            Object.keys(values || {}).forEach(function (k) {
                var el = card.querySelector('[data-f=' + k + ']'); if (!el) return;
                if (el.type === 'checkbox') el.checked = !!values[k]; else el.value = values[k] == null ? '' : values[k];
            });
            refresh(); card.scrollIntoView({ block: 'center', behavior: 'smooth' });
            card.querySelector('[data-f=question]').focus({ preventScroll: true });
        }

        box.addEventListener('input', function (e) { var c = e.target.closest('.sv-qcard'); if (c) { sync(c); if (e.target.matches('[data-f=category],[data-f=required]')) refresh(); } });
        box.addEventListener('change', function (e) { var c = e.target.closest('.sv-qcard'); if (c) refresh(); });
        box.addEventListener('click', function (e) {
            var b = e.target.closest('[data-move]'); if (!b) return;
            var c = b.closest('.sv-qcard');
            if (b.dataset.move === 'up' && c.previousElementSibling) box.insertBefore(c, c.previousElementSibling);
            if (b.dataset.move === 'down' && c.nextElementSibling) box.insertBefore(c.nextElementSibling, c);
            if (b.dataset.move === 'remove') c.remove();
            refresh();
        });
        document.getElementById('sv-add').addEventListener('click', function () { add({ type: 'scale' }); });
        document.getElementById('sv-bank-add').addEventListener('click', function () {
            var id = document.getElementById('sv-bank').value; if (!id) return;
            var b = bank.filter(function (x) { return String(x.id) === id; })[0];
            add({ bank: b.id, question: b.question, type: b.type, category: b.category, answer_set: b.set, help: b.help });
        });
        refresh();
    })();
    </script>
@endsection
