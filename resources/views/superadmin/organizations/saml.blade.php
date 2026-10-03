<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('SAML settings') }} - {{ config('app.name') }}</title>
</head>
<body>
    <header>
        <h1>{{ __('SAML settings for :organization', ['organization' => $organization->name]) }}</h1>
        <a href="{{ route('admin.dashboard') }}">{{ __('Back to organizations') }}</a>
    </header>

    <main>
        @if (session('status'))
            <p role="status">{{ session('status') }}</p>
        @endif

        <dl>
            <dt>{{ __('Service provider entity ID') }}</dt>
            <dd><code>{{ $spEntityId }}</code></dd>
            <dt>{{ __('Assertion consumer service URL') }}</dt>
            <dd><code>{{ $acsUrl }}</code></dd>
        </dl>

        <form method="POST" action="{{ route('admin.organizations.saml.update', $organization) }}">
            @csrf
            @method('PUT')

            <label for="idp_entity_id">{{ __('Identity provider entity ID') }}</label>
            <input id="idp_entity_id" name="idp_entity_id" type="text" value="{{ old('idp_entity_id', $connection?->idp_entity_id) }}" required>
            @error('idp_entity_id')
                <p role="alert">{{ $message }}</p>
            @enderror

            <label for="sso_url">{{ __('Identity provider SSO URL') }}</label>
            <input id="sso_url" name="sso_url" type="url" value="{{ old('sso_url', $connection?->sso_url) }}" required>
            @error('sso_url')
                <p role="alert">{{ $message }}</p>
            @enderror

            <label for="x509_certificate">{{ __('Identity provider signing certificate') }}</label>
            <textarea id="x509_certificate" name="x509_certificate" rows="8" autocomplete="off" placeholder="{{ __('Leave blank to keep the saved certificate.') }}">{{ old('x509_certificate') }}</textarea>
            <p>{{ __('Paste the IdP X.509 certificate in PEM format. The saved value is encrypted and never displayed.') }}</p>
            @error('x509_certificate')
                <p role="alert">{{ $message }}</p>
            @enderror

            <label for="email_attribute">{{ __('Email attribute name') }}</label>
            <input id="email_attribute" name="email_attribute" type="text" value="{{ old('email_attribute', $connection?->email_attribute ?? 'email') }}" required>
            @error('email_attribute')
                <p role="alert">{{ $message }}</p>
            @enderror

            <label for="name_attribute">{{ __('Display-name attribute name') }}</label>
            <input id="name_attribute" name="name_attribute" type="text" value="{{ old('name_attribute', $connection?->name_attribute ?? 'name') }}" required>
            @error('name_attribute')
                <p role="alert">{{ $message }}</p>
            @enderror

            <label>
                <input type="checkbox" name="enabled" value="1" @checked(old('enabled', $connection?->enabled ?? false))>
                {{ __('Enable SAML sign-in for this organization') }}
            </label>

            <button type="submit">{{ __('Save SAML settings') }}</button>
        </form>
    </main>
</body>
</html>
