# Tenant access

Tenant browser URLs use `/t/{organization-slug}` while
`TENANCY_RESOLUTION=path`. Production installations can use organization
subdomains by setting `TENANCY_RESOLUTION=subdomain` and configuring wildcard
DNS. The initial tenant administrator is created while an organization is
provisioned.
