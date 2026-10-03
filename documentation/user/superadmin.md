# Superadmin back office

The central back office is available at `/admin`. Superadmin accounts are
separate from tenant users and are stored in the central database. No default
superadmin credentials are seeded.

Run `php artisan migrate` against the central database, then create the first
account interactively:

```sh
php artisan superadmin:create
```

The command prompts for a name, email, and password. Passwords must be at least
12 characters and are stored as hashes. Sign in at `/admin/login` to review
organizations and activate or suspend tenant access. Suspending an organization
does not delete its database or tenant data.

Open an organization's **Configure SAML** page to register its IdP entity ID,
HTTPS SSO URL, X.509 signing certificate, and email/name attribute names. The
page displays the service provider entity ID and assertion consumer service
(ACS) URL to register with the IdP. The certificate is encrypted at rest and is
not redisplayed after saving. SAML sign-in remains off until explicitly enabled.

SAML endpoints are organization-scoped. Only signed responses and assertions
from the configured IdP are accepted, and authentication responses must match a
recent, single-use sign-in request. For path-based local development, configure
the IdP's ACS as the URL displayed by the back office; in subdomain deployments,
configure wildcard tenant DNS and TLS first.

SAML is just-in-time provisioned on first sign-in. Ensure the configured IdP
only releases authoritative email attributes for users allowed into that
organization; each account is then bound to its signed SAML NameID.
