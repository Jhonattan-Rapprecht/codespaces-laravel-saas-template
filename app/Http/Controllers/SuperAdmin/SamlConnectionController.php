<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Saml\SamlService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SamlConnectionController extends Controller
{
    public function edit(Organization $organization, SamlService $saml): View
    {
        return view('superadmin.organizations.saml', [
            'organization' => $organization,
            'connection' => $organization->samlConnection,
            'spEntityId' => $saml->endpoint($organization, 'saml/metadata'),
            'acsUrl' => $saml->endpoint($organization, 'saml/acs'),
        ]);
    }

    public function update(Request $request, Organization $organization, SamlService $saml): RedirectResponse
    {
        $validated = $request->validate([
            'idp_entity_id' => ['required', 'string', 'max:2048'],
            'sso_url' => [
                'required',
                'url',
                'max:2048',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $parts = parse_url((string) $value);
                    $isHttps = ($parts['scheme'] ?? null) === 'https';
                    $isLocalHttp = app()->environment('local')
                        && ($parts['scheme'] ?? null) === 'http'
                        && in_array($parts['host'] ?? null, ['localhost', '127.0.0.1', '[::1]'], true);

                    if ((! $isHttps && ! $isLocalHttp) || isset($parts['user']) || isset($parts['pass'])) {
                        $fail(__('The SSO URL must use HTTPS, except for local loopback URLs in local development.'));
                    }
                },
            ],
            'x509_certificate' => [
                'nullable',
                'string',
                'max:16384',
                function (string $attribute, mixed $value, \Closure $fail) use ($saml): void {
                    if (! is_string($value) || trim($value) === '') {
                        return;
                    }

                    $certificate = $saml->normalizeCertificate($value);
                    $pem = "-----BEGIN CERTIFICATE-----\n".chunk_split($certificate, 64, "\n")."-----END CERTIFICATE-----\n";

                    if ($certificate === '' || @openssl_x509_read($pem) === false) {
                        $fail(__('Enter a valid X.509 certificate.'));
                    }
                },
            ],
            'email_attribute' => ['required', 'string', 'max:255'],
            'name_attribute' => ['required', 'string', 'max:255'],
        ]);

        $connection = $organization->samlConnection()->firstOrNew();
        $certificate = trim((string) ($validated['x509_certificate'] ?? ''));

        if ($certificate === '' && ! $connection->exists) {
            throw ValidationException::withMessages([
                'x509_certificate' => __('An IdP signing certificate is required for the first configuration.'),
            ]);
        }

        $connection->fill([
            'idp_entity_id' => $validated['idp_entity_id'],
            'sso_url' => $validated['sso_url'],
            'email_attribute' => $validated['email_attribute'],
            'name_attribute' => $validated['name_attribute'],
            'enabled' => $request->boolean('enabled'),
        ]);

        if ($certificate !== '') {
            $connection->x509_certificate = $saml->normalizeCertificate($certificate);
        }

        $organization->samlConnection()->save($connection);

        return redirect()
            ->route('admin.organizations.saml.edit', $organization)
            ->with('status', __('SAML connection saved.'));
    }
}
