@extends('admin.layouts.plain')

@section('title', 'Admin')

@php $user = auth()->user(); @endphp

@section('content')
    @include('admin.partials.page-head', [
        'title' => 'Howdy, '.strtok($user->name, ' '),
        'sub' => 'Choose an app. You see the apps your role allows.',
        'actions' => (Gate::allows('manage-apps') ? '<a class="btn cyan" href="'.route('apps.create').'">+ New Admin App</a>' : '')
            .'<a class="btn ghost" href="/" target="_blank" rel="noopener">View website</a>',
    ])
    @if ($denied)
        <div class="banner-note">Your role ({{ $user->role?->name }}) does not include {{ $denied['name'] }}. Ask an administrator to add it in Sheriff → Roles.</div>
    @endif
    <div class="tiles">
        @foreach ($apps as $key => $a)
            @if ($user->hasPerm($key))
                <a class="tile" href="{{ $a['url'] }}">
                    <span class="ico">@include('admin.partials.icon', ['name' => $a['icon']])</span>
                    <h2>{{ $a['name'] }}</h2><p>{{ $a['desc'] }}</p><span class="open">Open {{ $a['name'] }} →</span>
                </a>
            @else
                <div class="tile locked" aria-disabled="true">
                    <span class="ico">@include('admin.partials.icon', ['name' => $a['icon']])</span>
                    <h2>{{ $a['name'] }}</h2><p>{{ $a['desc'] }}</p><span class="open">No access</span>
                </div>
            @endif
        @endforeach
        @can('manage-apps')
            <a class="tile tile-new" href="{{ route('apps.create') }}">
                <span class="ico">+</span><h2>New Admin App</h2><p>Add your own app with a tile here and a sidebar of links.</p><span class="open">Create →</span>
            </a>
        @endcan
    </div>
@endsection
