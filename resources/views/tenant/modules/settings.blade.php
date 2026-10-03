<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Settings') }} - {{ $organization->name }}</title>
</head>
<body>
    <header>
        <h1>{{ __('Organization settings') }}</h1>
        <a href="{{ app(\App\Tenancy\TenantUrlGenerator::class)->to($organization) }}">{{ __('Dashboard') }}</a>
    </header>

    <main>
        <dl>
            <dt>{{ __('Organization') }}</dt>
            <dd>{{ $organization->name }}</dd>
            <dt>{{ __('Sign-in method') }}</dt>
            <dd>{{ $samlEnabled ? __('SAML single sign-on enabled') : __('SAML single sign-on not configured') }}</dd>
        </dl>
    </main>
</body>
</html>
