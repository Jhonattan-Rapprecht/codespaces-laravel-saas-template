<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrganizationController extends Controller
{
    public function index(): View
    {
        return view('superadmin.organizations.index', [
            'organizations' => Organization::query()
                ->withCount('domains')
                ->orderBy('name')
                ->paginate(25),
        ]);
    }

    public function updateStatus(Request $request, Organization $organization): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:active,suspended'],
        ]);

        $organization->newQuery()
            ->whereKey($organization->getKey())
            ->update(['status' => $validated['status']]);

        return redirect()
            ->route('admin.dashboard')
            ->with('status', __('Organization status updated.'));
    }
}
