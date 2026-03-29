# Make sure this file has executable permissions, run `chmod +x railway/init-app.sh`

# Exit the script if any command fails
set -e

composer install --no-dev --no-interaction

mkdir -p ../config/jwt
if [ -n "$JWT_PRIVATE_KEY_BASE64" ] && [ -n "$JWT_PUBLIC_KEY_BASE64" ]; then
    echo "$JWT_PRIVATE_KEY_BASE64" | base64 -d > ../config/jwt/private.pem
    echo "$JWT_PUBLIC_KEY_BASE64" | base64 -d > ../config/jwt/public.pem
else
    php bin/console lexik:jwt:generate-keypair --overwrite
fi

php bin/console doctrine:migrations:migrate --no-interaction

php bin/console app:import-products

frankenphp run --config /etc/frankenphp/Caddyfile
