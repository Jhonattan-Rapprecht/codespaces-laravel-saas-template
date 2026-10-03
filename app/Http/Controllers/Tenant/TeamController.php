<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use App\Tenancy\TenantUrlGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class TeamController extends Controller
{
    public function store(Request $request, TenantUrlGenerator $urls): RedirectResponse
    {
        $organization = $this->organization();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::exists('roles', 'key')],
            'password' => ['required', 'string', Password::min(12)],
        ]);

        DB::transaction(function () use ($validated): void {
            $user = User::query()->create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
            ]);
            $user->roles()->sync([Role::query()->where('key', $validated['role'])->value('id')]);
        });

        return $this->back($organization, $urls, __('User created.'));
    }

    public function updateRole(Request $request, string $organizationSlug, int $user, TenantUrlGenerator $urls): RedirectResponse
    {
        $organization = $this->organization();
        $member = User::query()->with('roles')->findOrFail($user);
        $validated = $request->validate([
            'role' => ['required', Rule::exists('roles', 'key')],
        ]);

        DB::transaction(function () use ($member, $validated): void {
            // Serialize concurrent demotions so the last administrator check holds.
            $adminIds = DB::table('role_user')
                ->join('roles', 'roles.id', '=', 'role_user.role_id')
                ->where('roles.key', User::ROLE_ADMIN)
                ->lockForUpdate()
                ->pluck('role_user.user_id');

            if ($member->isTenantAdmin()
                && $validated['role'] !== User::ROLE_ADMIN
                && $adminIds->unique()->count() <= 1) {
                throw ValidationException::withMessages([
                    'role' => __('The organization must keep at least one administrator.'),
                ]);
            }

            $member->roles()->sync([Role::query()->where('key', $validated['role'])->value('id')]);
        });

        return $this->back($organization, $urls, __('Role updated.'));
    }

    public function updatePassword(Request $request, string $organizationSlug, int $user, TenantUrlGenerator $urls): RedirectResponse
    {
        $organization = $this->organization();
        $member = User::query()->findOrFail($user);
        $validated = $request->validate([
            'password' => ['required', 'string', Password::min(12)],
        ]);

        $member->forceFill(['password' => $validated['password'], 'remember_token' => null])->save();

        return $this->back($organization, $urls, __('Password updated.'));
    }

    private function organization(): Organization
    {
        $organization = Organization::query()->findOrFail(tenant('id'));

        abort_unless(
            $organization->modules()
                ->wherePivot('enabled', true)
                ->where('modules.enabled', true)
                ->where('modules.slug', 'team')
                ->exists(),
            404,
        );

        return $organization;
    }

    private function back(Organization $organization, TenantUrlGenerator $urls, string $status): RedirectResponse
    {
        return redirect()->to($urls->to($organization, 'modules/team'))->with('status', $status);
    }
}
