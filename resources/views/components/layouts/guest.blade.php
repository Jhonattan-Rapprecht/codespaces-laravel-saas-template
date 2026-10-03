@props(['title', 'heading', 'subheading' => null])
<x-layouts.base :title="$title">
    <main class="flex min-h-full flex-col items-center justify-center px-4 py-12">
        <div class="mb-6 flex items-center gap-2 text-indigo-600">
            <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3l8 4.5v9L12 21l-8-4.5v-9L12 3z"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 12l8-4.5M12 12v9M12 12L4 7.5"/></svg>
            <span class="text-lg font-semibold text-slate-900">{{ config('app.name') }}</span>
        </div>
        <div class="card w-full max-w-md p-8">
            <h1 class="text-xl font-semibold">{{ $heading }}</h1>
            @if ($subheading)
                <p class="mt-1 text-sm text-slate-500">{{ $subheading }}</p>
            @endif
            <div class="mt-6">{{ $slot }}</div>
        </div>
    </main>
</x-layouts.base>
