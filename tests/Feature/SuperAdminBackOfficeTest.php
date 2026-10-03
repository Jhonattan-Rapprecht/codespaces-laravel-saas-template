<?php

namespace Tests\Feature;

use App\Models\SuperAdmin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SuperAdminBackOfficeTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_routes_require_a_superadmin_session(): void
    {
        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_superadmin_can_sign_in_and_view_organizations(): void
    {
        $admin = SuperAdmin::query()->create([
            'name' => 'Platform Admin',
            'email' => 'platform@example.test',
            'password' => 'a-long-test-password',
        ]);

        DB::table('organizations')->insert([
            'id' => 'org-1',
            'name' => 'Example Organization',
            'slug' => 'example',
            'database_host' => '127.0.0.1',
            'database_name' => 'tenant_example',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withSession(['_token' => 'csrf-test-token'])
            ->post(route('admin.login.store'), [
                '_token' => 'csrf-test-token',
                'email' => 'platform@example.test',
                'password' => 'a-long-test-password',
            ])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin, 'superadmin');

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Example Organization')
            ->assertSee('example');
    }

    public function test_superadmin_can_suspend_an_organization(): void
    {
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame('sqlite', config('tenancy.database.central_connection'));

        $admin = SuperAdmin::query()->create([
            'name' => 'Platform Admin',
            'email' => 'platform@example.test',
            'password' => 'a-long-test-password',
        ]);

        DB::table('organizations')->insert([
            'id' => 'org-1',
            'name' => 'Example Organization',
            'slug' => 'example',
            'database_host' => '127.0.0.1',
            'database_name' => 'tenant_example',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin, 'superadmin')
            ->withSession(['_token' => 'csrf-test-token'])
            ->patch(route('admin.organizations.status', 'org-1'), [
                '_token' => 'csrf-test-token',
                'status' => 'suspended',
            ])
            ->assertRedirect(route('admin.dashboard'))
            ->assertSessionHas('status', 'Organization status updated.');

        $this->assertDatabaseHas('organizations', [
            'id' => 'org-1',
            'status' => 'suspended',
        ]);
    }

    public function test_invalid_organization_status_is_rejected(): void
    {
        $admin = SuperAdmin::query()->create([
            'name' => 'Platform Admin',
            'email' => 'platform@example.test',
            'password' => 'a-long-test-password',
        ]);

        DB::table('organizations')->insert([
            'id' => 'org-1',
            'name' => 'Example Organization',
            'slug' => 'example',
            'database_host' => '127.0.0.1',
            'database_name' => 'tenant_example',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin, 'superadmin')
            ->withSession(['_token' => 'csrf-test-token'])
            ->patch(route('admin.organizations.status', 'org-1'), [
                '_token' => 'csrf-test-token',
                'status' => 'deleted',
            ])
            ->assertSessionHasErrors('status');

        $this->assertDatabaseHas('organizations', [
            'id' => 'org-1',
            'status' => 'active',
        ]);
    }
}
