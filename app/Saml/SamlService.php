<?php

namespace App\Saml;

use App\Models\Organization;
use App\Models\OrganizationSamlConnection;
use OneLogin\Saml2\Auth;
use OneLogin\Saml2\Constants;

class SamlService
{
    public function auth(Organization $organization, OrganizationSamlConnection $connection): Auth
    {
        return new Auth($this->settings($organization, $connection));
    }

    public function endpoint(Organization $organization, string $path): string
    {
        $appUrl = parse_url((string) config('app.url'));
        $scheme = $appUrl['scheme'] ?? 'https';
        $host = $appUrl['host'] ?? 'localhost';
        $port = isset($appUrl['port']) ? ':'.$appUrl['port'] : '';

        if (config('tenancy.resolution') === 'subdomain') {
            $host = $organization->slug.'.'.$host;
            $prefix = '';
        } else {
            $prefix = '/t/'.rawurlencode($organization->slug);
        }

        return $scheme.'://'.$host.$port.$prefix.'/'.ltrim($path, '/');
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
                'entityId' => $this->endpoint($organization, 'saml/metadata'),
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
