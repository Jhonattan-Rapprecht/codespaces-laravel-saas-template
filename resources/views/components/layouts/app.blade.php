@props(['title', 'heading', 'subheading' => null, 'back' => null, 'backLabel' => null, 'badge' => null])
<x-layouts.base :title="$title">
    <header class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-4 sm:px-6">
            <div class="flex items-center gap-3">
                <svg class="h-7 w-7 text-indigo-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3l8 4.5v9L12 21l-8-4.5v-9L12 3z"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 12l8-4.5M12 12v9M12 12L4 7.5"/></svg>
                <span class="font-semibold">{{ $badge ?? config('app.name') }}</span>
            </div>
            @isset($actions)
                <div class="flex items-center gap-3 text-sm">{{ $actions }}</div>
            @endisset
        </div>
    </header>

    <main class="mx-auto max-w-6xl px-4 py-8 sm:px-6">
        @if ($back)
            <a href="{{ $back }}" class="mb-4 inline-flex items-center text-sm font-medium text-indigo-600 hover:text-indigo-500">&larr; {{ $backLabel }}</a>
        @endif
        <div class="mb-6">
            <h1 class="text-2xl font-semibold tracking-tight">{{ $heading }}</h1>
            @if ($subheading)
                <p class="mt-1 text-sm text-slate-500">{{ $subheading }}</p>
            @endif
        </div>
        @if (session('status'))
            <div class="alert-success mb-6" role="status">{{ session('status') }}</div>
        @endif
        {{ $slot }}
    </main>
</x-layouts.base>
