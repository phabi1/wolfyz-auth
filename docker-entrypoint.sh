#!/bin/sh
set -e

if [ -f "/app/composer.json" ] && [ ! -d "/app/vendor" ]; then
    composer install --no-interaction --no-progress --working-dir=/app
fi

# Wait for MySQL to accept connections, then apply the schema (idempotent).
if [ -n "$DB_HOST" ]; then
    echo "Waiting for database at $DB_HOST:$DB_PORT..."
    for i in $(seq 1 30); do
        if php -r "new PDO('mysql:host=$DB_HOST;port=$DB_PORT', getenv('DB_USERNAME'), getenv('DB_PASSWORD'));" 2>/dev/null; then
            break
        fi
        sleep 1
    done
    php /app/bin/migrate.php || true
fi

# Generate the RSA keypair used to sign ID tokens / access tokens on first boot.
PRIVATE_KEY="${OAUTH2_PRIVATE_KEY:-/app/certs/oauth2-private.key}"
PUBLIC_KEY="$(dirname "$PRIVATE_KEY")/oauth2-public.key"
PASSPHRASE="${OAUTH2_PASSPHRASE:-}"

mkdir -p "$(dirname "$PRIVATE_KEY")"

if [ ! -f "$PRIVATE_KEY" ]; then
    echo "Generating OAuth2 signing key pair at $(dirname "$PRIVATE_KEY")"
    if [ -n "$PASSPHRASE" ]; then
        openssl genrsa -aes256 -passout "pass:$PASSPHRASE" -out "$PRIVATE_KEY" 2048
        openssl rsa -in "$PRIVATE_KEY" -passin "pass:$PASSPHRASE" -pubout -out "$PUBLIC_KEY"
    else
        openssl genrsa -out "$PRIVATE_KEY" 2048
        openssl rsa -in "$PRIVATE_KEY" -pubout -out "$PUBLIC_KEY"
    fi
    chmod 600 "$PRIVATE_KEY"
    chmod 644 "$PUBLIC_KEY"
    chown www-data:www-data "$PRIVATE_KEY" "$PUBLIC_KEY"
fi

exec "$@"
