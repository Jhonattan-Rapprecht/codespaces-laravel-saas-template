<x-layouts.app
    :title="__('Dashboard').' - '.$organization->name"
    :heading="__('Welcome back, :name', ['name' => auth()->user()->name])"
    :subheading="__('Your modules')"
    :badge="$organization->name"
>
    <x-slot:actions>
        <span class="hidden text-slate-500 sm:inline">{{ auth()->user()->email }}</span>
        <form method="POST" action="{{ $urls->to($organization, 'logout') }}">
            @csrf
            <button type="submit" class="btn-secondary">{{ __('Sign out') }}</button>
        </form>
    </x-slot:actions>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($modules as $module)
            <a href="{{ $urls->to($organization, 'modules/'.$module->slug) }}" class="card block p-5 transition hover:border-indigo-300 hover:shadow-md">
                <h2 class="font-semibold text-slate-900">{{ $module->name }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ $module->description }}</p>
                <span class="mt-4 inline-block text-sm font-medium text-indigo-600">{{ __('Open') }} &rarr;</span>
            </a>
        @empty
            <p class="card col-span-full p-6 text-center text-sm text-slate-500">{{ __('No modules have been enabled for this organization yet.') }}</p>
        @endforelse
    </div>
</x-layouts.app>
