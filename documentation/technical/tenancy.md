# Tenancy

## Database isolation

Each organization has a dedicated MySQL database. The central `organizations`
record stores `database_host` and `database_name`, plus encrypted database
credentials. Provisioning creates a database user granted only the privileges
needed on its own tenant database, then creates the database, migrates
`database/migrations/tenant`, and seeds default roles and the initial tenant
administrator.

The current deployment uses one MySQL server, but storing the host and database
name means an organization can be moved to another MySQL server or container
without changing application code. Docker-per-tenant and Kubernetes-per-tenant
are deliberately not used: they add operational complexity without providing
additional isolation over separate MySQL databases at this stage.

## Resolution

Set `TENANCY_RESOLUTION=subdomain` in production. A tenant then resolves from
`{slug}.{APP_URL host}`. Set `TENANCY_RESOLUTION=path` for Codespaces and local
development; tenant URLs use `/t/{slug}`. In path mode an `X-Tenant` header is
also accepted for non-browser development clients.

`APP_URL` and `TRUSTED_PROXIES` must match the environment. Codespaces forwarded
requests must use `TRUSTED_PROXIES=*` so the public HTTPS URL is recognized.

Tenant web sessions are scoped to the organization initialized for the
request. Reusing an authenticated browser session on another tenant is rejected
with HTTP 403 rather than carrying the first tenant's identity across databases.
SAML authentication request state is also bound to its initiating organization
and consumed once.

Feature coverage for dashboard authentication, enabled-module access, session
scoping, and cross-organization SAML request rejection runs with
`php artisan test --filter=TenantAccessTest`. Tenant test databases are isolated
SQLite files under `storage/framework/testing` and are removed after each test.
