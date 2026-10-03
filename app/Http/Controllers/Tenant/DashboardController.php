<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Tenancy\TenantUrlGenerator;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(TenantUrlGenerator $urls): View
    {
        $organization = Organization::query()->findOrFail(tenant('id'));
        $modules = $organization->modules()
            ->wherePivot('enabled', true)
            ->where('modules.enabled', true)
            ->orderBy('modules.name')
            ->get();

        return view('tenant.dashboard', [
            'organization' => $organization,
            'modules' => $modules,
            'urls' => $urls,
        ]);
    }
}
