<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Modules') }} - {{ config('app.name') }}</title>
</head>
<body>
    <header>
        <h1>{{ __('Modules for :organization', ['organization' => $organization->name]) }}</h1>
        <a href="{{ route('admin.dashboard') }}">{{ __('Back to organizations') }}</a>
    </header>

    <main>
        @if (session('status'))
            <p role="status">{{ session('status') }}</p>
        @endif

        <form method="POST" action="{{ route('admin.organizations.modules.update', $organization) }}">
            @csrf
            @method('PUT')
            @forelse ($modules as $module)
                <fieldset>
                    <legend>{{ $module->name }}</legend>
                    <p>{{ $module->description }}</p>
                    <label for="module-{{ $module->id }}">
                        <input
                            id="module-{{ $module->id }}"
                            type="checkbox"
                            name="modules[]"
                            value="{{ $module->slug }}"
                            @checked(in_array($module->id, $enabledModuleIds, true))
                        >
                        {{ __('Enable for this organization') }}
                    </label>
                </fieldset>
            @empty
                <p>{{ __('No modules are available. Run php artisan db:seed to install the default module catalog.') }}</p>
            @endforelse
            @error('modules')
                <p role="alert">{{ $message }}</p>
            @enderror
            @error('modules.*')
                <p role="alert">{{ $message }}</p>
            @enderror
            <button type="submit">{{ __('Save modules') }}</button>
        </form>
    </main>
</body>
</html>
