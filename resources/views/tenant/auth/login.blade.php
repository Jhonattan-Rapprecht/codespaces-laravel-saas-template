<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Sign in') }} - {{ $organization->name }}</title>
</head>
<body>
    <main>
        <h1>{{ __('Sign in to :organization', ['organization' => $organization->name]) }}</h1>
        @if ($samlEnabled)
            <a href="{{ $samlLoginUrl }}">{{ __('Continue with organization SSO') }}</a>
        @else
            <form method="POST" action="{{ $passwordLoginUrl }}">
                @csrf
                <div>
                    <label for="email">{{ __('Email') }}</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
                    @error('email')
                        <p role="alert">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="password">{{ __('Password') }}</label>
                    <input id="password" type="password" name="password" required autocomplete="current-password">
                </div>
                <label><input type="checkbox" name="remember" value="1"> {{ __('Remember me') }}</label>
                <button type="submit">{{ __('Sign in') }}</button>
            </form>
        @endif
    </main>
</body>
</html>
