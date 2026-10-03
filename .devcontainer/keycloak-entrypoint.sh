#!/bin/sh

set -eu

password_file=/opt/keycloak/data/.bootstrap-admin-password

if [ ! -s "$password_file" ]; then
    password=${KC_BOOTSTRAP_ADMIN_PASSWORD:-}

    if [ -z "$password" ]; then
        password=$(dd if=/dev/urandom bs=48 count=1 2>/dev/null | base64 | tr -d '\n')
    fi

    if [ -z "$password" ]; then
        printf '%s\n' 'Unable to generate the Keycloak bootstrap password.' >&2
        exit 1
    fi

    umask 077
    printf '%s\n' "$password" > "$password_file"
fi

KC_BOOTSTRAP_ADMIN_PASSWORD=$(cat "$password_file")
export KC_BOOTSTRAP_ADMIN_PASSWORD

exec /opt/keycloak/bin/kc.sh start-dev --import-realm
