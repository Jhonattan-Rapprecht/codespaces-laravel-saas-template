<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Tenancy\TenantUrlGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(TenantUrlGenerator $urls): View
    {
        $organization = Organization::query()->findOrFail(tenant('id'));

        return view('tenant.auth.login', [
            'organization' => $organization,
            'samlEnabled' => $organization->samlConnection?->enabled ?? false,
            'passwordLoginUrl' => $urls->to($organization, 'login'),
            'samlLoginUrl' => $urls->to($organization, 'saml/login'),
        ]);
    }

    public function store(Request $request, TenantUrlGenerator $urls): RedirectResponse
    {
        $organization = Organization::query()->findOrFail(tenant('id'));

        abort_if($organization->samlConnection?->enabled, 404);

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $key = 'tenant-login:'.$organization->getKey().'|'.Str::transliterate(Str::lower($credentials['email']).'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'email' => __('Too many login attempts. Please try again in :seconds seconds.', [
                    'seconds' => RateLimiter::availableIn($key),
                ]),
            ]);
        }

        if (! Auth::guard('web')->attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($key, 60);

            throw ValidationException::withMessages([
                'email' => __('These credentials do not match our records.'),
            ]);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();

        return redirect()->to($urls->to($organization, ''));
    }

    public function destroy(Request $request, TenantUrlGenerator $urls): RedirectResponse
    {
        $organization = Organization::query()->findOrFail(tenant('id'));
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->to($urls->to($organization, 'login'));
    }
}
