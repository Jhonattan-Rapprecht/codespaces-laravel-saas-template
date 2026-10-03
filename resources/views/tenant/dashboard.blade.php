<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Dashboard') }} - {{ $organization->name }}</title>
</head>
<body>
    <header>
        <h1>{{ $organization->name }}</h1>
        <p>{{ __('Signed in as :name', ['name' => auth()->user()->name]) }}</p>
        <form method="POST" action="{{ $urls->to($organization, 'logout') }}">
            @csrf
            <button type="submit">{{ __('Sign out') }}</button>
        </form>
    </header>

    <main>
        <h2>{{ __('Your modules') }}</h2>
        @forelse ($modules as $module)
            <article>
                <h3><a href="{{ $urls->to($organization, 'modules/'.$module->slug) }}">{{ $module->name }}</a></h3>
                <p>{{ $module->description }}</p>
            </article>
        @empty
            <p>{{ __('No modules have been enabled for this organization yet.') }}</p>
        @endforelse
    </main>
</body>
</html>
