<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\OrganizationSamlConnection;
use App\Models\SuperAdmin;
use App\Saml\SamlService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SamlConfigurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_can_save_an_encrypted_saml_connection(): void
    {
        $organization = $this->createOrganization();
        $certificate = $this->certificate();

        $this->withSession(['_token' => 'csrf-test-token'])
            ->actingAs($this->createSuperAdmin(), 'superadmin')
            ->put(route('admin.organizations.saml.update', $organization), [
                '_token' => 'csrf-test-token',
                'idp_entity_id' => 'https://idp.example.test/metadata',
                'sso_url' => 'https://idp.example.test/sso',
                'x509_certificate' => $certificate,
                'email_attribute' => 'email',
                'name_attribute' => 'displayName',
                'enabled' => '1',
            ])
            ->assertRedirect(route('admin.organizations.saml.edit', $organization))
            ->assertSessionHas('status', 'SAML connection saved.');

        $connection = $organization->fresh()->samlConnection;
        $this->assertInstanceOf(OrganizationSamlConnection::class, $connection);
        $this->assertTrue($connection->enabled);
        $this->assertSame($this->normalizedCertificate($certificate), $connection->x509_certificate);
        $this->assertDatabaseMissing('organization_saml_connections', [
            'organization_id' => $organization->getKey(),
            'x509_certificate' => $this->normalizedCertificate($certificate),
        ]);
    }

    public function test_superadmin_cannot_configure_an_insecure_remote_sso_url(): void
    {
        $organization = $this->createOrganization();

        $this->withSession(['_token' => 'csrf-test-token'])
            ->actingAs($this->createSuperAdmin(), 'superadmin')
            ->put(route('admin.organizations.saml.update', $organization), [
                '_token' => 'csrf-test-token',
                'idp_entity_id' => 'https://idp.example.test/metadata',
                'sso_url' => 'http://idp.example.test/sso',
                'x509_certificate' => $this->certificate(),
                'email_attribute' => 'email',
                'name_attribute' => 'displayName',
            ])
            ->assertSessionHasErrors('sso_url');

        $this->assertDatabaseMissing('organization_saml_connections', [
            'organization_id' => $organization->getKey(),
        ]);
    }

    public function test_superadmin_cannot_save_a_malformed_signing_certificate(): void
    {
        $organization = $this->createOrganization();

        $this->withSession(['_token' => 'csrf-test-token'])
            ->actingAs($this->createSuperAdmin(), 'superadmin')
            ->put(route('admin.organizations.saml.update', $organization), [
                '_token' => 'csrf-test-token',
                'idp_entity_id' => 'https://idp.example.test/metadata',
                'sso_url' => 'https://idp.example.test/sso',
                'x509_certificate' => 'not-a-certificate',
                'email_attribute' => 'email',
                'name_attribute' => 'displayName',
            ])
            ->assertSessionHasErrors('x509_certificate');

        $this->assertDatabaseMissing('organization_saml_connections', [
            'organization_id' => $organization->getKey(),
        ]);
    }

    public function test_saml_settings_require_signed_assertions_and_exact_destination_validation(): void
    {
        $organization = new Organization(['id' => 'org-1', 'slug' => 'example']);
        $connection = new OrganizationSamlConnection([
            'idp_entity_id' => 'https://idp.example.test/metadata',
            'sso_url' => 'https://idp.example.test/sso',
            'x509_certificate' => $this->normalizedCertificate($this->certificate()),
        ]);

        $auth = app(SamlService::class)->auth($organization, $connection);
        $security = $auth->getSettings()->getSecurityData();
        $metadata = $auth->getSettings()->getSPMetadata();

        $this->assertTrue($auth->getSettings()->isStrict());
        $this->assertTrue($security['wantMessagesSigned']);
        $this->assertTrue($security['wantAssertionsSigned']);
        $this->assertTrue($security['destinationStrictlyMatches']);
        $this->assertTrue($security['rejectUnsolicitedResponsesWithInResponseTo']);
        $this->assertStringContainsString('/t/example/saml/acs', $metadata);
    }

    private function createOrganization(): Organization
    {
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

        return Organization::query()->findOrFail('org-1');
    }

    private function createSuperAdmin(): SuperAdmin
    {
        return SuperAdmin::query()->create([
            'name' => 'Platform Admin',
            'email' => 'platform@example.test',
            'password' => 'a-long-test-password',
        ]);
    }

    private function certificate(): string
    {
        $privateKey = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);
        $this->assertNotFalse($privateKey);

        $request = openssl_csr_new(['commonName' => 'Test IdP'], $privateKey);
        $this->assertNotFalse($request);

        $certificate = openssl_csr_sign($request, null, $privateKey, 1);
        $this->assertNotFalse($certificate);
        $this->assertTrue(openssl_x509_export($certificate, $certificatePem));

        return $certificatePem;
    }

    private function normalizedCertificate(string $certificate): string
    {
        return app(SamlService::class)->normalizeCertificate($certificate);
    }
}
