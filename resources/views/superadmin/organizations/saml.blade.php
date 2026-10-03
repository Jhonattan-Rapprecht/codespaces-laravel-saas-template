<x-layouts.app
    :title="__('SAML settings').' - '.config('app.name')"
    :heading="__('SAML settings for :organization', ['organization' => $organization->name])"
    :badge="__('Superadmin back office')"
    :back="route('admin.dashboard')"
    :backLabel="__('Back to organizations')"
>
    <div class="grid gap-6 lg:grid-cols-3">
        <dl class="card h-fit space-y-4 p-6 lg:col-span-1">
            <h2 class="text-sm font-semibold text-slate-900">{{ __('Register with your IdP') }}</h2>
            <div>
                <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Service provider entity ID') }}</dt>
                <dd class="mt-1 break-all"><code class="text-xs">{{ $spEntityId }}</code></dd>
            </div>
            <div>
                <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Assertion consumer service URL') }}</dt>
                <dd class="mt-1 break-all"><code class="text-xs">{{ $acsUrl }}</code></dd>
            </div>
        </dl>

        <form method="POST" action="{{ route('admin.organizations.saml.update', $organization) }}" class="card space-y-5 p-6 lg:col-span-2">
            @csrf
            @method('PUT')

            <div>
                <label for="idp_entity_id" class="field-label">{{ __('Identity provider entity ID') }}</label>
                <input id="idp_entity_id" name="idp_entity_id" type="text" value="{{ old('idp_entity_id', $connection?->idp_entity_id) }}" required class="field-input">
                @error('idp_entity_id')
                    <p role="alert" class="field-error">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="sso_url" class="field-label">{{ __('Identity provider SSO URL') }}</label>
                <input id="sso_url" name="sso_url" type="url" value="{{ old('sso_url', $connection?->sso_url) }}" required class="field-input">
                @error('sso_url')
                    <p role="alert" class="field-error">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="x509_certificate" class="field-label">{{ __('Identity provider signing certificate') }}</label>
                <textarea id="x509_certificate" name="x509_certificate" rows="6" autocomplete="off" class="field-input font-mono text-xs" placeholder="{{ __('Leave blank to keep the saved certificate.') }}">{{ old('x509_certificate') }}</textarea>
                <p class="field-hint">{{ __('Paste the IdP X.509 certificate in PEM format. The saved value is encrypted and never displayed.') }}</p>
                @error('x509_certificate')
                    <p role="alert" class="field-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="email_attribute" class="field-label">{{ __('Email attribute name') }}</label>
                    <input id="email_attribute" name="email_attribute" type="text" value="{{ old('email_attribute', $connection?->email_attribute ?? 'email') }}" required class="field-input">
                    @error('email_attribute')
                        <p role="alert" class="field-error">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="name_attribute" class="field-label">{{ __('Display-name attribute name') }}</label>
                    <input id="name_attribute" name="name_attribute" type="text" value="{{ old('name_attribute', $connection?->name_attribute ?? 'name') }}" required class="field-input">
                    @error('name_attribute')
                        <p role="alert" class="field-error">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <label class="flex items-center gap-2 text-sm text-slate-700">
                <input type="checkbox" name="enabled" value="1" class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" @checked(old('enabled', $connection?->enabled ?? false))>
                {{ __('Enable SAML sign-in for this organization') }}
            </label>

            <button type="submit" class="btn">{{ __('Save SAML settings') }}</button>
        </form>
    </div>
</x-layouts.app>
