# Keycloak development identity provider

The devcontainer starts Keycloak 26.8 on port `8081` and imports the
`saas-dev` realm with a SAML client for the demo organization. This realm is for
local development only; it has HTTP enabled and uses an ephemeral development
database volume.

The bootstrap administrator username is `admin`. Its password is generated at
first start and stored with mode `0600` inside the Keycloak data volume. On the
Docker host, retrieve it with:

```sh
docker compose -f .devcontainer/docker-compose.yml exec keycloak \
  cat /opt/keycloak/data/.bootstrap-admin-password
```

Do not copy this local credential into source control or use it outside the
development Keycloak instance.

Open the forwarded **Keycloak** port and create a test user in the `saas-dev`
realm. Set the user's email and first name; the imported SAML client emits those
as `email` and `name` attributes and signs both the response and assertion.

In the superadmin back office, configure the demo organization with:

- **IdP entity ID:** `http://localhost:8081/realms/saas-dev`
- **SSO URL:** `http://localhost:8081/realms/saas-dev/protocol/saml`
- **Signing certificate:** copy the active realm signing certificate from
  `http://localhost:8081/realms/saas-dev/protocol/saml/descriptor` into PEM form
- **Email attribute:** `email`
- **Display-name attribute:** `name`

Register the service-provider entity ID and ACS URL shown in the back office.
The imported Keycloak client defaults to the local `/t/demo/saml/acs` URL. For
Codespaces or a different local host, update the client's POST ACS URL and
redirect URI to the exact ACS URL shown by the application. The forwarded
Keycloak SSO URL must also be reachable by the user's browser.

The Keycloak configuration is development-only and does not replace production
TLS, secret management, or tenant-specific IdP configuration.
