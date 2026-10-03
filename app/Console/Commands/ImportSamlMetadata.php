<?php

namespace App\Console\Commands;

use App\Models\Organization;
use App\Saml\SamlService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use OneLogin\Saml2\IdPMetadataParser;
use Throwable;

class ImportSamlMetadata extends Command
{
    protected $signature = 'saml:import-metadata
        {organization : Organization slug}
        {url : IdP SAML metadata URL}
        {--forwarded-host= : Public host the IdP should report (sent as X-Forwarded-Host with HTTPS)}
        {--enable : Enable SAML sign-in for the organization}';

    protected $description = 'Configure an organization\'s SAML connection from IdP metadata';

    public function handle(SamlService $saml): int
    {
        $organization = Organization::query()->where('slug', $this->argument('organization'))->first();

        if (! $organization) {
            $this->components->error('Organization not found.');

            return self::FAILURE;
        }

        $headers = [];

        if ($host = $this->option('forwarded-host')) {
            $headers = ['X-Forwarded-Host' => $host, 'X-Forwarded-Proto' => 'https'];
        }

        try {
            $response = Http::withHeaders($headers)->timeout(10)->get($this->argument('url'))->throw();
            $idp = IdPMetadataParser::parseXML($response->body())['idp'] ?? [];
        } catch (Throwable $e) {
            $this->components->error('Unable to read the IdP metadata: '.$e->getMessage());

            return self::FAILURE;
        }

        $ssoUrl = $idp['singleSignOnService']['url'] ?? null;
        $certificate = $idp['x509cert'] ?? null;

        if (! ($idp['entityId'] ?? null) || ! $ssoUrl || ! $certificate) {
            $this->components->error('The metadata is missing an entity ID, SSO URL or signing certificate.');

            return self::FAILURE;
        }

        if (! str_starts_with($ssoUrl, 'https://') && ! app()->environment('local')) {
            $this->components->error('The SSO URL must use HTTPS.');

            return self::FAILURE;
        }

        $connection = $organization->samlConnection()->firstOrNew();
        $connection->fill([
            'idp_entity_id' => $idp['entityId'],
            'sso_url' => $ssoUrl,
            'email_attribute' => $connection->email_attribute ?? 'email',
            'name_attribute' => $connection->name_attribute ?? 'name',
            'enabled' => $this->option('enable') ? true : (bool) $connection->enabled,
        ]);
        $connection->x509_certificate = $saml->normalizeCertificate($certificate);
        $organization->samlConnection()->save($connection);

        $this->components->info("SAML connection saved for {$organization->slug} (SSO ".($connection->enabled ? 'enabled' : 'disabled').').');
        $this->line('SP entity ID: '.$saml->entityId($organization));
        $this->line('ACS URL: '.$saml->endpoint($organization, 'saml/acs'));

        return self::SUCCESS;
    }
}
