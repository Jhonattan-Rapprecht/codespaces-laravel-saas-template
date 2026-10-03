<?php

namespace App\Saml;

use App\Models\Organization;
use App\Models\OrganizationSamlConnection;
use App\Tenancy\TenantUrlGenerator;
use OneLogin\Saml2\Auth;
use OneLogin\Saml2\Constants;

class SamlService
{
    public function __construct(private TenantUrlGenerator $urls) {}

    public function auth(Organization $organization, OrganizationSamlConnection $connection): Auth
    {
        return new Auth($this->settings($organization, $connection));
    }

    public function endpoint(Organization $organization, string $path): string
    {
        return $this->urls->to($organization, $path);
    }

    public function entityId(Organization $organization): string
    {
        return 'urn:laravel-saas:'.$organization->slug;
    }

    public function normalizeCertificate(string $certificate): string
    {
        return preg_replace(
            '/-----BEGIN CERTIFICATE-----|-----END CERTIFICATE-----|\s+/',
            '',
            trim($certificate),
        ) ?? '';
    }

    private function settings(Organization $organization, OrganizationSamlConnection $connection): array
    {
        return [
            'strict' => true,
            'debug' => false,
            'sp' => [
                'entityId' => $this->entityId($organization),
                'assertionConsumerService' => [
                    'url' => $this->endpoint($organization, 'saml/acs'),
                    'binding' => Constants::BINDING_HTTP_POST,
                ],
            ],
            'idp' => [
                'entityId' => $connection->idp_entity_id,
                'singleSignOnService' => [
                    'url' => $connection->sso_url,
                ],
                'x509cert' => $connection->x509_certificate,
            ],
            'security' => [
                'authnRequestsSigned' => false,
                'wantMessagesSigned' => true,
                'wantAssertionsSigned' => true,
                'wantNameId' => true,
                'requestedAuthnContext' => false,
                'destinationStrictlyMatches' => true,
                'rejectUnsolicitedResponsesWithInResponseTo' => true,
            ],
        ];
    }
}
