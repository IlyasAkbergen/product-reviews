# Make sure this file has executable permissions, run `chmod +x railway/init-consumer.sh`

# Exit the script if any command fails
set -e

cp .env.example .env

composer install --no-interaction

php bin/console messenger:consume async --limit=500 --memory-limit=128M -vv
