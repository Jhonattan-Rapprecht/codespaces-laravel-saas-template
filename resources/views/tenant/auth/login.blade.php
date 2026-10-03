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
            <p role="status">{{ __('Organization SSO has not been configured. Contact your administrator.') }}</p>
        @endif
    </main>
</body>
</html>
