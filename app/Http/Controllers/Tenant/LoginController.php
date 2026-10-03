<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Tenancy\TenantUrlGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(TenantUrlGenerator $urls): View
    {
        $organization = Organization::query()->findOrFail(tenant('id'));

        return view('tenant.auth.login', [
            'organization' => $organization,
            'samlEnabled' => $organization->samlConnection?->enabled ?? false,
            'samlLoginUrl' => $urls->to($organization, 'saml/login'),
        ]);
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
