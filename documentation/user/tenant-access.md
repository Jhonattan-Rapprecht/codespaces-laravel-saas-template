# Tenant access

Tenant browser URLs use `/t/{organization-slug}` while
`TENANCY_RESOLUTION=path`. Production installations can use organization
subdomains by setting `TENANCY_RESOLUTION=subdomain` and configuring wildcard
DNS. The initial tenant administrator is created while an organization is
provisioned.

Sign in at `/t/{organization-slug}/login`. When the organization's SAML
connection is enabled, the page offers SSO only and password sign-in is
rejected. When SAML is disabled or not configured, the page shows an email and
password form instead. Password sign-in is rate limited per email and IP address.
Users created through SAML have random passwords, so they cannot use the form
until an administrator sets a password. Tenant sessions are bound to a single
organization; using the same browser session for a different organization
requires signing out first.

After sign-in, the tenant dashboard shows modules enabled for that organization.
The superadmin can enable the available Team and Settings modules on the
organization's **Configure modules** page. Team displays tenant users and their
roles; Settings shows the organization's name and SAML sign-in status.

Run `php artisan db:seed` after central migrations to install the default module
catalog as well as the demo organization.
