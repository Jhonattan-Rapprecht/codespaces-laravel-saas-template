<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OrganizationModuleController extends Controller
{
    public function edit(Organization $organization): View
    {
        return view('superadmin.organizations.modules', [
            'organization' => $organization,
            'modules' => Module::query()->where('enabled', true)->orderBy('name')->get(),
            'enabledModuleIds' => $organization->modules()
                ->wherePivot('enabled', true)
                ->pluck('modules.id')
                ->all(),
        ]);
    }

    public function update(Request $request, Organization $organization): RedirectResponse
    {
        $validated = $request->validate([
            'modules' => ['sometimes', 'array'],
            'modules.*' => ['string', 'distinct'],
        ]);

        $selectedSlugs = $validated['modules'] ?? [];
        $selectedModules = Module::query()
            ->where('enabled', true)
            ->whereIn('slug', $selectedSlugs)
            ->get(['id', 'slug']);

        if ($selectedModules->count() !== count($selectedSlugs)) {
            throw ValidationException::withMessages([
                'modules' => __('One or more selected modules are not available.'),
            ]);
        }

        $organization->modules()->sync($selectedModules->pluck('id')->all());

        return redirect()
            ->route('admin.organizations.modules.edit', $organization)
            ->with('status', __('Organization modules updated.'));
    }
}
