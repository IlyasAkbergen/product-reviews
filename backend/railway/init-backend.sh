#!/bin/bash
# Make sure this file has executable permissions, run `chmod +x railway/init-app.sh`

# Exit the script if any command fails
set -e

composer install --no-dev --no-interaction

php bin/console lexik:jwt:generate-keypair --overwrite

php bin/console doctrine:migrations:migrate --no-interaction

php bin/console doctrine:fixtures:load --no-interaction

php bin/console app:import-products
