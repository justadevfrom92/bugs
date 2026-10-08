@extends('admin.layouts.app')

@section('crumb', $agent->exists ? $agent->name : 'New Agent')

@section('content')
    @include('admin.partials.page-head', ['title' => $agent->exists ? 'Edit '.$agent->name : 'New Agent', 'sub' => 'What the agent does, which model it runs on, and its instructions.',
        'actions' => $agent->exists ? '<a class="btn ghost" href="'.route('deputy.agents.test', $agent).'">Test</a>' : null])
    <form method="post" action="{{ $agent->exists ? route('deputy.agents.update', $agent) : route('deputy.agents.store') }}" class="panel simple-form">@csrf @if ($agent->exists) @method('put') @endif
        <div class="panel-body form-grid">
            <label for="name">Name</label><input id="name" name="name" required maxlength="80" value="{{ old('name', $agent->name) }}">
            <label for="activity">Activity</label><select id="activity" name="activity">@foreach (config('deputy.activities') as $v => $l)<option value="{{ $v }}" @selected(old('activity', $agent->activity) === $v)>{{ $l }}</option>@endforeach</select>
            <label for="description">Description</label><input id="description" name="description" maxlength="250" value="{{ old('description', $agent->description) }}">
            <label for="ai_model_id">Model</label><select id="ai_model_id" name="ai_model_id">@foreach ($models->groupBy(fn ($m) => $m->providerLabel()) as $p => $list)<optgroup label="{{ $p }}">@foreach ($list as $m)<option value="{{ $m->id }}" @selected(old('ai_model_id', $agent->ai_model_id) == $m->id)>{{ $m->name }} ({{ $m->model_id }}){{ $m->status !== 'available' ? ' — '.$m->status : '' }}</option>@endforeach</optgroup>@endforeach</select>
            <label for="fallback_model_id">Fallback model</label><div><select id="fallback_model_id" name="fallback_model_id"><option value="">None</option>@foreach ($models->groupBy(fn ($m) => $m->providerLabel()) as $p => $list)<optgroup label="{{ $p }}">@foreach ($list as $m)<option value="{{ $m->id }}" @selected(old('fallback_model_id', $agent->fallback_model_id) == $m->id)>{{ $m->name }} ({{ $m->model_id }})</option>@endforeach</optgroup>@endforeach</select><div class="help">Used when the main model can't answer (not set up, unreachable or declined).</div></div>
            <label for="effort">Effort</label><div><select id="effort" name="effort" style="max-width:20ch">@foreach (config('deputy.efforts') as $v => $l)<option value="{{ $v }}" @selected(old('effort', $agent->effort) === $v)>{{ $l }}</option>@endforeach</select><div class="help">How hard Claude models think before answering. Higher is more thorough and uses more tokens.</div></div>
            <label for="max_tokens">Max tokens</label><input id="max_tokens" name="max_tokens" type="number" min="256" max="64000" required value="{{ old('max_tokens', $agent->max_tokens) }}" style="max-width:14ch">
            <label for="system_prompt">Instructions</label><textarea id="system_prompt" name="system_prompt" required maxlength="20000" style="min-height:220px;font-family:inherit">{{ old('system_prompt', $agent->system_prompt) }}</textarea>
            <label for="active">Active</label><label class="check"><input type="hidden" name="active" value="0"><input id="active" type="checkbox" name="active" value="1" @checked(old('active', $agent->active))> Taking conversations</label>
        </div>
        <div class="panel-body actions"><button class="btn cyan">{{ $agent->exists ? 'Save Agent' : 'Add Agent' }}</button><a class="btn ghost" href="{{ route('deputy.agents') }}">Cancel</a></div>
    </form>
@endsection
