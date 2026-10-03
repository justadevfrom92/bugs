<!DOCTYPE html>
<html lang="en">
<head>
    @include('admin.partials.head')
    <title>Admin Sign In · {{ config('brand.name') }}</title>
</head>
<body>
<div class="login-wrap">
    <form class="login" method="post" action="{{ route('admin.login.attempt') }}">
        @csrf
        <div class="brand">
            <img src="/shared/img/logo-mark-on-dark.svg" alt="" width="36" height="36">
            <div><strong>{{ config('brand.wordmark') }}</strong><span>Admin Tools</span></div>
        </div>
        @if (session('status'))<div class="flash ok">{{ session('status') }}</div>@endif
        <div class="field"><label for="email">Email</label><input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required autofocus></div>
        <div class="field"><label for="password">Password</label><input id="password" name="password" type="password" autocomplete="current-password" required></div>
        <label class="check"><input type="checkbox" name="remember" value="1"> Keep me signed in</label>
        <div class="err" role="alert">{{ $errors->first() }}</div>
        <button class="btn cyan">Sign In</button>
        <a href="/" class="muted" style="text-align:center;font-size:.85rem">← Back to website</a>
    </form>
</div>
</body>
</html>
