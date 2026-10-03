@php $user = auth()->user(); @endphp
<!DOCTYPE html>
<html lang="en">
<head>
    @include('admin.partials.head')
    <title>{{ config('brand.name') }} Admin</title>
</head>
<body>
<div class="launcher">
    <header class="launcher-top"><div class="in">
        <div class="brand"><img src="/shared/img/logo-mark-on-dark.svg" alt="" width="36" height="36"><div><strong>{{ config('brand.wordmark') }}</strong><span>Admin Tools</span></div></div>
        <div class="user-chip"><span class="avatar">{{ $user->initials() }}</span><span><span>{{ $user->name }}</span><small>{{ $user->role?->name }}</small></span></div>
        <form method="post" action="{{ route('admin.logout') }}" class="inline">@csrf<button class="btn sm ghost">Logout</button></form>
    </div></header>
    <main class="launcher-main">
        @include('admin.partials.page-head', [
            'title' => 'Howdy, '.strtok($user->name, ' '),
            'sub' => 'Choose an app. You see the apps your role allows.',
            'actions' => '<a class="btn ghost" href="/" target="_blank" rel="noopener">View website</a>',
        ])
        @if ($denied)
            <div class="banner-note">Your role ({{ $user->role?->name }}) does not include {{ $denied }}. Ask an administrator to add it in Sheriff → Roles.</div>
        @endif
        <div class="tiles">
            @foreach ($apps as $key => $a)
                @if ($user->hasPerm($key))
                    <a class="tile" href="{{ route($a['home']) }}">
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
        </div>
    </main>
</div>
<div class="toast" id="toast" role="status" aria-live="polite" data-message="{{ session('status') }}"></div>
</body>
</html>
