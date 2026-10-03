<x-layouts.guest
    :title="config('app.name')"
    :heading="config('app.name')"
    :subheading="__('Multi-tenant SaaS platform')"
>
    <p class="text-sm text-slate-600">
        {{ __('Open your organization’s sign-in page at /t/your-organization, or sign in to the platform back office.') }}
    </p>
    <a href="{{ route('admin.login') }}" class="btn mt-6 w-full">{{ __('Superadmin sign in') }}</a>
</x-layouts.guest>
