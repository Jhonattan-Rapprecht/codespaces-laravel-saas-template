<x-layouts.app
    :title="__('Settings').' - '.$organization->name"
    :heading="__('Organization settings')"
    :badge="$organization->name"
    :back="app(\App\Tenancy\TenantUrlGenerator::class)->to($organization)"
    :backLabel="__('Dashboard')"
>
    <dl class="card divide-y divide-slate-100">
        <div class="grid gap-1 px-6 py-4 sm:grid-cols-3">
            <dt class="text-sm font-medium text-slate-500">{{ __('Organization') }}</dt>
            <dd class="text-sm text-slate-900 sm:col-span-2">{{ $organization->name }}</dd>
        </div>
        <div class="grid gap-1 px-6 py-4 sm:grid-cols-3">
            <dt class="text-sm font-medium text-slate-500">{{ __('Sign-in method') }}</dt>
            <dd class="text-sm text-slate-900 sm:col-span-2">
                <span class="badge {{ $samlEnabled ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                    {{ $samlEnabled ? __('SAML single sign-on enabled') : __('SAML single sign-on not configured') }}
                </span>
            </dd>
        </div>
    </dl>
</x-layouts.app>
