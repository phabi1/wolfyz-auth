#!/usr/bin/env bash
#
# Post-deployment tasks, run on the server over SSH by the deploy workflow
# once the files are synchronized:
#   ssh user@server "cd /path/to/auth && bash bin/deploy.sh"
#
# Environment:
#   APP_ENV  application environment (default: production)
#   PHP_BIN  PHP CLI binary (default: php)

set -euo pipefail

cd "$(dirname "$0")/.."

export APP_ENV="${APP_ENV:-production}"

log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*"
}

# Dependencies are installed with PHP 8.2 in CI
MIN_PHP_VERSION=80200

# On shared hosting `php` is often php-cgi (no -r, prints HTTP headers):
# look for a CLI binary >= 8.2 in the usual locations
find_php_cli() {
    local candidates=()
    if [ -n "${PHP_BIN:-}" ]; then
        candidates+=("$PHP_BIN")
    fi
    local version
    for version in 8.5 8.4 8.3 8.2; do
        candidates+=(
            "php$version-cli"
            "/usr/bin/php$version-cli"
            "php$version"
            "php${version/./}"
            "/usr/local/php$version/bin/php"
            "/opt/plesk/php/$version/bin/php"
            "/opt/cpanel/ea-php${version/./}/root/usr/bin/php"
            "/opt/alt/php${version/./}/usr/bin/php"
            "/usr/bin/php$version"
        )
    done
    candidates+=(php-cli php)

    local candidate
    for candidate in "${candidates[@]}"; do
        if ! command -v "$candidate" >/dev/null 2>&1; then
            continue
        fi
        if ! "$candidate" -v 2>/dev/null | head -n 1 | grep -q '(cli)'; then
            continue
        fi
        if [ "$("$candidate" -r 'echo PHP_VERSION_ID;' 2>/dev/null)" -ge "$MIN_PHP_VERSION" ] 2>/dev/null; then
            echo "$candidate"
            return 0
        fi
    done

    echo "No PHP CLI >= 8.2 found. Tried: ${candidates[*]}" >&2
    echo "Set PHP_BIN to the PHP CLI binary of the server." >&2
    return 1
}

PHP_BIN="$(find_php_cli)"

log "Deploying (APP_ENV=$APP_ENV, $PHP_BIN: $("$PHP_BIN" -r 'echo "PHP ", PHP_VERSION;'))"

log "Running database migrations"
"$PHP_BIN" vendor/bin/phinx migrate --configuration phinx.php

# Cleared after the migrations so entity and service caches are rebuilt
# from the new code. Done in the shell rather than through bin/console:
# the console kernel itself boots from cache/services.php, so a stale
# cache could not be relied on to clear itself.
log "Clearing cache"
mkdir -p cache
find cache -mindepth 1 ! -name '.gitkeep' -delete

# The OIDC signing keys live in certs/ (excluded from the sync): they are
# created once on the server and must survive deployments
if ! ls certs/*-private.key >/dev/null 2>&1; then
    log "WARNING: no signing key in certs/, run: $PHP_BIN bin/console oidc:mkcert oauth2"
fi

echo "$(date '+%Y-%m-%d %H:%M:%S') - Update completed" >> deploy.log
log "Update completed"
