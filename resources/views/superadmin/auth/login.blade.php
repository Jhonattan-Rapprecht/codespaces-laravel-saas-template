<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Superadmin sign in') }} - {{ config('app.name') }}</title>
</head>
<body>
    <main>
        <h1>{{ __('Superadmin sign in') }}</h1>
        <form method="POST" action="{{ route('admin.login.store') }}">
            @csrf
            <label for="email">{{ __('Email') }}</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username">
            @error('email')
                <p role="alert">{{ $message }}</p>
            @enderror

            <label for="password">{{ __('Password') }}</label>
            <input id="password" name="password" type="password" required autocomplete="current-password">
            @error('password')
                <p role="alert">{{ $message }}</p>
            @enderror

            <label>
                <input type="checkbox" name="remember" value="1">
                {{ __('Remember me') }}
            </label>
            <button type="submit">{{ __('Sign in') }}</button>
        </form>
    </main>
</body>
</html>
