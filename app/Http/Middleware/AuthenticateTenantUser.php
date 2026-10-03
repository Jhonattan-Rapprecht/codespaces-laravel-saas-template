<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use App\Tenancy\TenantUrlGenerator;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateTenantUser
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::guard('web')->check()) {
            $organization = Organization::query()->findOrFail(tenant('id'));

            return redirect()->to(app(TenantUrlGenerator::class)->to($organization, 'login'));
        }

        return $next($request);
    }
}
