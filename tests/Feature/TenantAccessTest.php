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

        foreach ($this->tenantDatabases as $tenantDatabase) {
            if (is_file($tenantDatabase['file'])) {
                unlink($tenantDatabase['file']);
            }

            if (is_dir($tenantDatabase['directory'])) {
                rmdir($tenantDatabase['directory']);
            }
        }

        parent::tearDown();
    }

    public function test_dashboard_requires_sign_in_and_login_is_saml_only(): void
    {
        $organization = $this->createOrganization('org-a', 'acme');

        $this->get(route('tenant.dashboard', ['organization' => $organization->slug]))
            ->assertRedirect(route('tenant.login', ['organization' => $organization->slug]));

        $this->get(route('tenant.login', ['organization' => $organization->slug]))
            ->assertOk()
            ->assertSee('Organization SSO has not been configured.')
            ->assertDontSee('Continue with organization SSO');

        $this->get(route('tenant.saml.login', ['organization' => $organization->slug]))
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
