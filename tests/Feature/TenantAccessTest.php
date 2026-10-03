<?php

namespace Tests\Feature;

use App\Models\Module;
use App\Models\Organization;
use App\Models\Role;
use App\Models\SamlAuthenticationRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class TenantAccessTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var list<array{file: string, directory: string}>
     */
    private array $tenantDatabases = [];

    protected function tearDown(): void
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }

        parent::tearDown();

        foreach ($this->tenantDatabases as $tenantDatabase) {
            if (is_file($tenantDatabase['file'])) {
                unlink($tenantDatabase['file']);
            }

            if (is_dir($tenantDatabase['directory'])) {
                rmdir($tenantDatabase['directory']);
            }
        }
    }

    public function test_dashboard_requires_sign_in_and_login_is_saml_only(): void
    {
        $organization = $this->createOrganization('org-a', 'acme');

        $this->get(route('tenant.dashboard', ['organization' => $organization->slug]))
            ->assertRedirect(route('tenant.login', ['organization' => $organization->slug]));

        $this->get(route('tenant.login', ['organization' => $organization->slug]))
            ->assertOk()
            ->assertSee('type="password"', false)
            ->assertDontSee('Continue with organization SSO');

        $this->get(route('tenant.saml.login', ['organization' => $organization->slug]))
            ->assertNotFound();
    }

    public function test_password_login_works_only_while_sso_is_disabled(): void
    {
        $organization = $this->createOrganization('org-a', 'acme');
        $this->createTenantUser($organization, 'member@acme.test');
        $slug = ['organization' => $organization->slug];
        $credentials = ['_token' => 'csrf-test-token', 'email' => 'member@acme.test', 'password' => 'a-long-tenant-password'];
        $this->withSession(['_token' => 'csrf-test-token']);

        $this->post(route('tenant.login.store', $slug), [...$credentials, 'password' => 'wrong'])
            ->assertSessionHasErrors('email');
        $this->assertGuest('web');

        $this->post(route('tenant.login.store', $slug), $credentials)
            ->assertRedirect();
        $this->assertAuthenticated('web');

        auth('web')->logout();
        $this->withSession(['_token' => 'csrf-test-token']);

        $organization->samlConnection()->create([
            'idp_entity_id' => 'https://idp.example.test/metadata',
            'sso_url' => 'https://idp.example.test/sso',
            'x509_certificate' => 'certificate',
            'email_attribute' => 'email',
            'name_attribute' => 'name',
            'enabled' => true,
        ]);

        $this->get(route('tenant.login', $slug))
            ->assertSee('Continue with organization SSO')
            ->assertDontSee('type="password"', false);
        $this->post(route('tenant.login.store', $slug), $credentials)->assertNotFound();
        $this->assertGuest('web');
    }

    public function test_only_tenant_admins_can_manage_team_users_and_roles(): void
    {
        $organization = $this->createOrganizationWithTeamModule('org-a', 'acme');
        $admin = $this->createTenantUserWithRole($organization, 'admin@acme.test', 'tenant-admin');
        $member = $this->createTenantUserWithRole($organization, 'member@acme.test', 'member');
        $slug = ['organization' => $organization->slug];
        $newUser = ['name' => 'New Person', 'email' => 'new@acme.test', 'role' => 'member', 'password' => 'a-long-initial-password'];

        $this->actingAs($member, 'web')
            ->withSession(['_token' => 'csrf-test-token'])
            ->post(route('tenant.team.store', $slug), $newUser + ['_token' => 'csrf-test-token'])
            ->assertForbidden();

        $this->actingAs($member, 'web')
            ->get(route('tenant.modules.show', $slug + ['slug' => 'team']))
            ->assertOk()
            ->assertDontSee('Add a user');

        $this->actingAs($admin, 'web')
            ->withSession(['_token' => 'csrf-test-token'])
            ->get(route('tenant.modules.show', $slug + ['slug' => 'team']))
            ->assertOk()
            ->assertSee('Add a user');

        $this->actingAs($admin, 'web')
            ->withSession(['_token' => 'csrf-test-token'])
            ->post(route('tenant.team.store', $slug), $newUser + ['_token' => 'csrf-test-token'])
            ->assertRedirect()
            ->assertSessionHas('status', 'User created.');

        $this->post(route('tenant.team.store', $slug), ['email' => 'new@acme.test'] + $newUser + ['_token' => 'csrf-test-token'])
            ->assertSessionHasErrors('email');

        $this->post(route('tenant.team.store', $slug), ['email' => 'weak@acme.test', 'password' => 'short', '_token' => 'csrf-test-token'] + $newUser)
            ->assertSessionHasErrors('password');

        tenancy()->initialize($organization);
        $created = User::query()->with('roles')->where('email', 'new@acme.test')->firstOrFail();
        $this->assertSame(['member'], $created->roles->pluck('key')->all());
        tenancy()->end();
    }

    public function test_last_tenant_admin_cannot_be_demoted_and_roles_can_change(): void
    {
        $organization = $this->createOrganizationWithTeamModule('org-a', 'acme');
        $admin = $this->createTenantUserWithRole($organization, 'admin@acme.test', 'tenant-admin');
        $member = $this->createTenantUserWithRole($organization, 'member@acme.test', 'member');
        $slug = ['organization' => $organization->slug];

        $this->actingAs($admin, 'web')->withSession(['_token' => 'csrf-test-token']);

        $this->patch(route('tenant.team.role', $slug + ['user' => $admin->id]), ['role' => 'member', '_token' => 'csrf-test-token'])
            ->assertSessionHasErrors('role');

        $this->patch(route('tenant.team.role', $slug + ['user' => $member->id]), ['role' => 'tenant-admin', '_token' => 'csrf-test-token'])
            ->assertSessionHas('status', 'Role updated.');

        $this->patch(route('tenant.team.role', $slug + ['user' => $admin->id]), ['role' => 'member', '_token' => 'csrf-test-token'])
            ->assertSessionHas('status', 'Role updated.');

        $this->patch(route('tenant.team.role', $slug + ['user' => $member->id]), ['role' => 'nonexistent', '_token' => 'csrf-test-token'])
            ->assertSessionHasErrors('role');
    }

    public function test_admin_can_set_a_password_that_works_for_password_login(): void
    {
        $organization = $this->createOrganizationWithTeamModule('org-a', 'acme');
        $admin = $this->createTenantUserWithRole($organization, 'admin@acme.test', 'tenant-admin');
        $member = $this->createTenantUserWithRole($organization, 'member@acme.test', 'member');
        $slug = ['organization' => $organization->slug];

        $this->actingAs($admin, 'web')
            ->withSession(['_token' => 'csrf-test-token'])
            ->put(route('tenant.team.password', $slug + ['user' => $member->id]), ['password' => 'short', '_token' => 'csrf-test-token'])
            ->assertSessionHasErrors('password');

        $this->put(route('tenant.team.password', $slug + ['user' => $member->id]), ['password' => 'brand-new-password-1', '_token' => 'csrf-test-token'])
            ->assertSessionHas('status', 'Password updated.');

        auth('web')->logout();
        $this->withSession(['_token' => 'csrf-test-token'])
            ->post(route('tenant.login.store', $slug), ['email' => 'member@acme.test', 'password' => 'brand-new-password-1', '_token' => 'csrf-test-token'])
            ->assertRedirect();
        $this->assertAuthenticatedAs($member->fresh(), 'web');
    }

    public function test_team_management_requires_the_team_module(): void
    {
        $organization = $this->createOrganization('org-a', 'acme');
        $admin = $this->createTenantUserWithRole($organization, 'admin@acme.test', 'tenant-admin');

        $this->actingAs($admin, 'web')
            ->withSession(['_token' => 'csrf-test-token'])
            ->post(route('tenant.team.store', ['organization' => $organization->slug]), [
                'name' => 'X', 'email' => 'x@acme.test', 'role' => 'member', 'password' => 'a-long-initial-password', '_token' => 'csrf-test-token',
            ])
            ->assertNotFound();
    }

    public function test_dashboard_lists_only_enabled_modules_and_team_access_is_tenant_scoped(): void
    {
        $organization = $this->createOrganization('org-a', 'acme');
        $team = Module::query()->create([
            'slug' => 'team',
            'name' => 'Team',
            'description' => 'View tenant users and their roles.',
            'enabled' => true,
        ]);
        Module::query()->create([
            'slug' => 'settings',
            'name' => 'Settings',
            'description' => 'View organization and sign-in configuration.',
            'enabled' => true,
        ]);
        $organization->modules()->attach($team->id, ['enabled' => true]);

        $user = $this->createTenantUser($organization, 'member@acme.test');

        $this->actingAs($user, 'web')
            ->get(route('tenant.dashboard', ['organization' => $organization->slug]))
            ->assertOk()
            ->assertSee('Team')
            ->assertDontSee('Settings');

        $this->get(route('tenant.modules.show', [
            'organization' => $organization->slug,
            'slug' => 'team',
        ]))
            ->assertOk()
            ->assertSee('member@acme.test')
            ->assertSee('Tenant member');

        $this->get(route('tenant.modules.show', [
            'organization' => $organization->slug,
            'slug' => 'settings',
        ]))->assertNotFound();
    }

    public function test_tenant_session_cannot_be_reused_for_another_organization(): void
    {
        $firstOrganization = $this->createOrganization('org-a', 'acme');
        $secondOrganization = $this->createOrganization('org-b', 'globex');
        $user = $this->createTenantUser($firstOrganization, 'member@acme.test');

        $this->actingAs($user, 'web')
            ->get(route('tenant.dashboard', ['organization' => $firstOrganization->slug]))
            ->assertOk()
            ->assertSessionHas('_tenant_id', $firstOrganization->getTenantKey());

        $this->get(route('tenant.login', ['organization' => $secondOrganization->slug]))
            ->assertForbidden();
    }

    public function test_saml_request_state_from_another_organization_is_rejected(): void
    {
        $firstOrganization = $this->createOrganization('org-a', 'acme');
        $secondOrganization = $this->createOrganization('org-b', 'globex');

        $secondOrganization->samlConnection()->create([
            'idp_entity_id' => 'https://idp.example.test/metadata',
            'sso_url' => 'https://idp.example.test/sso',
            'x509_certificate' => 'certificate-for-request-state-test',
            'email_attribute' => 'email',
            'name_attribute' => 'name',
            'enabled' => true,
        ]);

        $relayState = Str::random(64);
        $state = SamlAuthenticationRequest::query()->create([
            'state_hash' => hash('sha256', $relayState),
            'organization_id' => $firstOrganization->getKey(),
            'request_id' => '_request-from-acme',
            'expires_at' => now()->addMinutes(5),
        ]);

        $this->post(route('tenant.saml.acs', ['organization' => $secondOrganization->slug]), [
            'SAMLResponse' => 'not-a-saml-response',
            'RelayState' => $relayState,
        ])->assertStatus(419);

        $this->assertNull($state->fresh()->consumed_at);
    }

    private function createOrganizationWithTeamModule(string $id, string $slug): Organization
    {
        $organization = $this->createOrganization($id, $slug);
        $team = Module::query()->create([
            'slug' => 'team',
            'name' => 'Team',
            'description' => 'View tenant users and their roles.',
            'enabled' => true,
        ]);
        $organization->modules()->attach($team->id, ['enabled' => true]);

        return $organization;
    }

    private function createTenantUserWithRole(Organization $organization, string $email, string $roleKey): User
    {
        tenancy()->initialize($organization);

        if (! Schema::hasTable('users')) {
            $this->assertSame(0, Artisan::call('migrate', [
                '--database' => 'tenant',
                '--path' => database_path('migrations/tenant'),
                '--realpath' => true,
                '--force' => true,
            ]), Artisan::output());
        }

        $user = User::query()->create(['name' => ucfirst(strstr($email, '@', true)), 'email' => $email, 'password' => 'a-long-tenant-password']);
        $role = Role::query()->firstOrCreate(['key' => $roleKey], ['name' => ucfirst($roleKey)]);
        $user->roles()->attach($role->id);

        tenancy()->end();

        return $user;
    }

    private function createOrganization(string $id, string $slug): Organization
    {
        $relativeDatabaseName = '../storage/framework/testing/tenant-'.Str::uuid().'/tenant.sqlite';
        $databaseFile = database_path($relativeDatabaseName);
        $databaseDirectory = dirname($databaseFile);
        $this->tenantDatabases[] = [
            'file' => $databaseFile,
            'directory' => $databaseDirectory,
        ];

        if (! mkdir($databaseDirectory, 0700, true)) {
            throw new \RuntimeException('Unable to create the isolated tenant test database directory.');
        }

        if (file_put_contents($databaseFile, '') === false) {
            throw new \RuntimeException('Unable to create the isolated tenant test database.');
        }

        DB::table('organizations')->insert([
            'id' => $id,
            'name' => ucfirst($slug).' Organization',
            'slug' => $slug,
            'database_host' => '127.0.0.1',
            'database_name' => $relativeDatabaseName,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Organization::query()->findOrFail($id);
    }

    private function createTenantUser(Organization $organization, string $email): User
    {
        tenancy()->initialize($organization);

        $exitCode = Artisan::call('migrate', [
            '--database' => 'tenant',
            '--path' => database_path('migrations/tenant'),
            '--realpath' => true,
            '--force' => true,
        ]);

        $this->assertSame(0, $exitCode, Artisan::output());

        $user = User::query()->create([
            'name' => 'Tenant member',
            'email' => $email,
            'password' => 'a-long-tenant-password',
        ]);
        $role = Role::query()->create([
            'key' => 'member',
            'name' => 'Tenant member',
        ]);
        $user->roles()->attach($role->id);

        tenancy()->end();

        return $user;
    }
}
