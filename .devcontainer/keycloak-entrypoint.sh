#!/bin/sh

set -eu

# Development-only Keycloak bootstrap. The admin password and the demo realm
# user's password come from the environment; never reuse them outside local
# development.

if [ -z "${KC_BOOTSTRAP_ADMIN_PASSWORD:-}" ]; then
    echo 'KC_BOOTSTRAP_ADMIN_PASSWORD must be set.' >&2
    exit 1
fi

if [ -z "${APP_PUBLIC_URL:-}" ]; then
    if [ -n "${CODESPACE_NAME:-}" ] && [ -n "${GITHUB_CODESPACES_PORT_FORWARDING_DOMAIN:-}" ]; then
        APP_PUBLIC_URL="https://${CODESPACE_NAME}-8000.${GITHUB_CODESPACES_PORT_FORWARDING_DOMAIN}"
    else
        APP_PUBLIC_URL="http://localhost:8000"
    fi
fi

import_dir=/opt/keycloak/data/import
mkdir -p "$import_dir"

# The realm is only imported when it does not exist yet, so the SAML client's
# ACS URL is rendered from the application's public URL on first start.
sed \
    -e "s|__APP_URL__|${APP_PUBLIC_URL%/}|g" \
    -e "s|__DEV_USER_PASSWORD__|${KEYCLOAK_DEV_USER_PASSWORD:-Demo-Sso-Pass-1}|g" \
    /opt/keycloak/realm-template/saas-dev-realm.json > "$import_dir/saas-dev-realm.json"

exec /opt/keycloak/bin/kc.sh start-dev --import-realm
