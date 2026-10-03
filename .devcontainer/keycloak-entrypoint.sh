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
# The Keycloak image is minimal and has no sed, so render with POSIX shell only.
replace_all() {
    rest=$1
    out=
    while [ "${rest#*"$2"}" != "$rest" ]; do
        out=$out${rest%%"$2"*}$3
        rest=${rest#*"$2"}
    done
    printf '%s' "$out$rest"
}

realm_json=$(cat /opt/keycloak/realm-template/saas-dev-realm.json)
realm_json=$(replace_all "$realm_json" __APP_URL__ "${APP_PUBLIC_URL%/}")
realm_json=$(replace_all "$realm_json" __DEV_USER_PASSWORD__ "${KEYCLOAK_DEV_USER_PASSWORD:-Demo-Sso-Pass-1}")
printf '%s\n' "$realm_json" > "$import_dir/saas-dev-realm.json"

exec /opt/keycloak/bin/kc.sh start-dev --import-realm
