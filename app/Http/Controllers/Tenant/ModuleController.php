<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Illuminate\View\View;

class ModuleController extends Controller
{
    public function show(string $organizationSlug, string $slug): View
    {
        $organization = Organization::query()
            ->where('slug', $organizationSlug)
            ->findOrFail(tenant('id'));
        $module = $organization->modules()
            ->wherePivot('enabled', true)
            ->where('modules.enabled', true)
            ->where('modules.slug', $slug)
            ->firstOrFail();

        return match ($module->slug) {
            'team' => view('tenant.modules.team', [
                'organization' => $organization,
                'members' => User::query()->with('roles')->orderBy('name')->get(),
                'roles' => Role::query()->orderBy('name')->get(),
                'canManage' => (bool) auth()->user()?->isTenantAdmin(),
            ]),
            'settings' => view('tenant.modules.settings', [
                'organization' => $organization,
                'samlEnabled' => $organization->samlConnection?->enabled ?? false,
            ]),
            default => abort(404),
        };
    }
}
