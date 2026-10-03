# Keycloak development identity provider

The devcontainer starts Keycloak 26.8 on port `8081` and imports the `saas-dev`
realm with a SAML client for the demo organization and one demo user. This realm
is for local development only; it has HTTP enabled, fixed credentials, and a
development database volume.

## Credentials (development only)

| Account | Where | Username | Password |
|---|---|---|---|
| Keycloak admin | `https://<codespace>-8081.app.github.dev` admin console | `admin` | `keycloak-dev-admin` |
| Realm user | `saas-dev` realm, signs in through SSO | `admin@demo.test` | `Demo-Sso-Pass-1` |

Override them with `KEYCLOAK_ADMIN_PASSWORD` and `KEYCLOAK_DEV_USER_PASSWORD`
in the environment before the Keycloak container first starts. Never reuse these
values outside local development.

The realm user's email matches the seeded demo tenant administrator, so SSO
signs in as the demo administrator.

## First start and resetting

Keycloak only imports the realm when it does not exist yet. The SAML client's ACS
URL and redirect URI are rendered from `APP_PUBLIC_URL` on that first start; in
Codespaces they default to `https://<codespace>-8000.app.github.dev`, otherwise
`http://localhost:8000`. After changing credentials or the public URL, rebuild the
devcontainer and remove the Keycloak volume so the realm is re-imported:

```sh
docker compose -f .devcontainer/docker-compose.yml down keycloak
docker volume rm "$(docker volume ls -q | grep keycloak-data)"
docker compose -f .devcontainer/docker-compose.yml up -d keycloak
```

(From the Codespace host terminal, not from inside the app container.)

## Connect the demo organization

A re-imported realm has new signing keys, so refresh the organization's SAML
connection from the realm metadata. From the app container:

```sh
php artisan saml:import-metadata demo \
  http://keycloak:8080/realms/saas-dev/protocol/saml/descriptor \
  --forwarded-host="${CODESPACE_NAME}-8081.${GITHUB_CODESPACES_PORT_FORWARDING_DOMAIN}" \
  --enable
```

`--forwarded-host` makes Keycloak report the browser-reachable URL as the entity
ID and SSO URL. Locally without Codespaces, use
`http://localhost:8081/realms/saas-dev/protocol/saml/descriptor` and omit the
option. The command prints the service-provider entity ID and ACS URL to
register with the IdP.

Then open `<APP_URL>/t/demo/login`, choose **Continue with organization SSO**,
and sign in as `admin@demo.test`. Make sure port `8081` is forwarded and
reachable by your browser.

The Keycloak configuration is development-only and does not replace production
TLS, secret management, or tenant-specific IdP configuration.
