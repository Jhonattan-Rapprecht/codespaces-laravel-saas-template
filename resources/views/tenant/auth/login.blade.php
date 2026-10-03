<x-layouts.guest
    :title="__('Sign in').' - '.$organization->name"
    :heading="__('Sign in to :organization', ['organization' => $organization->name])"
    :subheading="$samlEnabled ? __('Use your organization’s single sign-on.') : __('Enter your email and password to continue.')"
>
    @if ($samlEnabled)
        <a href="{{ $samlLoginUrl }}" class="btn w-full">{{ __('Continue with organization SSO') }}</a>
    @else
        <form method="POST" action="{{ $passwordLoginUrl }}" class="space-y-4">
            @csrf
            <div>
                <label for="email" class="field-label">{{ __('Email') }}</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="field-input">
                @error('email')
                    <p role="alert" class="field-error">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="password" class="field-label">{{ __('Password') }}</label>
                <input id="password" type="password" name="password" required autocomplete="current-password" class="field-input">
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" name="remember" value="1" class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                {{ __('Remember me') }}
            </label>
            <button type="submit" class="btn w-full">{{ __('Sign in') }}</button>
        </form>
    @endif
</x-layouts.guest>
