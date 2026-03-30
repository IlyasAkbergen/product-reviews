# Make sure this file has executable permissions, run `chmod +x railway/init-backend.sh`

# Exit the script if any command fails
set -e

cp .env.example .env

composer install --no-interaction

JWT_DIR="config/jwt"
mkdir -p "$JWT_DIR"

printf '%s' "$JWT_PRIVATE_KEY_BASE64" | base64 -d > "$JWT_DIR/private.pem"
printf '%s' "$JWT_PUBLIC_KEY_BASE64" | base64 -d > "$JWT_DIR/public.pem"

chmod 600 "$JWT_DIR/private.pem"
chmod 644 "$JWT_DIR/public.pem"

php bin/console doctrine:migrations:migrate --no-interaction

php bin/console app:import-products

frankenphp run --config /etc/frankenphp/Caddyfile
