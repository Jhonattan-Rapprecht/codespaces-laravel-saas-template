<x-layouts.app
    :title="__('Modules').' - '.config('app.name')"
    :heading="__('Modules for :organization', ['organization' => $organization->name])"
    :badge="__('Superadmin back office')"
    :back="route('admin.dashboard')"
    :backLabel="__('Back to organizations')"
>
    <form method="POST" action="{{ route('admin.organizations.modules.update', $organization) }}" class="space-y-4">
        @csrf
        @method('PUT')
        <div class="grid gap-4 sm:grid-cols-2">
            @forelse ($modules as $module)
                <label for="module-{{ $module->id }}" class="card flex cursor-pointer items-start gap-3 p-5 transition hover:border-indigo-300">
                    <input
                        id="module-{{ $module->id }}"
                        type="checkbox"
                        name="modules[]"
                        value="{{ $module->slug }}"
                        class="mt-1 h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                        @checked(in_array($module->id, $enabledModuleIds, true))
                    >
                    <span>
                        <span class="block font-semibold text-slate-900">{{ $module->name }}</span>
                        <span class="block text-sm text-slate-500">{{ $module->description }}</span>
                        <span class="mt-1 block text-xs text-slate-400">{{ __('Enable for this organization') }}</span>
                    </span>
                </label>
            @empty
                <p class="card col-span-full p-6 text-sm text-slate-500">{{ __('No modules are available. Run php artisan db:seed to install the default module catalog.') }}</p>
            @endforelse
        </div>
        @error('modules')
            <p role="alert" class="field-error">{{ $message }}</p>
        @enderror
        @error('modules.*')
            <p role="alert" class="field-error">{{ $message }}</p>
        @enderror
        <button type="submit" class="btn">{{ __('Save modules') }}</button>
    </form>
</x-layouts.app>
