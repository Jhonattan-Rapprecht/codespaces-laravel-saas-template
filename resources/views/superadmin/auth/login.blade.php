<x-layouts.guest :title="__('Superadmin sign in').' - '.config('app.name')" :heading="__('Superadmin sign in')" :subheading="__('Platform back office')">
    <form method="POST" action="{{ route('admin.login.store') }}" class="space-y-4">
        @csrf
        <div>
            <label for="email" class="field-label">{{ __('Email') }}</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="field-input">
            @error('email')
                <p role="alert" class="field-error">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="password" class="field-label">{{ __('Password') }}</label>
            <input id="password" name="password" type="password" required autocomplete="current-password" class="field-input">
            @error('password')
                <p role="alert" class="field-error">{{ $message }}</p>
            @enderror
        </div>
        <label class="flex items-center gap-2 text-sm text-slate-600">
            <input type="checkbox" name="remember" value="1" class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
            {{ __('Remember me') }}
        </label>
        <button type="submit" class="btn w-full">{{ __('Sign in') }}</button>
    </form>
</x-layouts.guest>
