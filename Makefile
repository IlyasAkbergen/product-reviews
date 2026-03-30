.PHONY: up down build restart logs shell migrate seed import-products composer-install jwt-keys copy-env setup test deploy

## Start all services
up:
	@[ -f .env ] || cp .env.example .env
	docker compose up -d --build

## Stop all services
down:
	docker compose down

## Build images without cache
build:
	docker compose build --no-cache

## Restart all services
restart:
	docker compose down && docker compose up -d

## Follow logs
logs:
	docker compose logs -f

## PHP shell
shell:
	docker compose exec app sh

## Run DB migrations
migrate:
	docker compose exec app php bin/console doctrine:migrations:migrate --no-interaction

## Load fixtures (demo user + sample data)
seed:
	docker compose exec app php bin/console doctrine:fixtures:load --no-interaction

## Import products from dummyjson.com
import-products:
	docker compose exec app php bin/console app:import-products

## Composer install
composer-install:
	docker compose exec app composer install

## Generate JWT keys
jwt-keys:
	docker compose exec app php bin/console lexik:jwt:generate-keypair --overwrite

# copy .env.example to .env if it doesn't exist, then run the full setup
copy-env:
	@[ -f .env ] || cp .env.example .env
	@[ -f backend/.env ] || cp backend/.env.example backend/.env

## Full setup from scratch
setup:
	@$(MAKE) copy-env
	@$(MAKE) up
	@echo "Waiting for services..."
	@sleep 10
	docker compose exec app composer install
	docker compose exec app php bin/console lexik:jwt:generate-keypair --overwrite
	docker compose exec app php bin/console doctrine:migrations:migrate --no-interaction
	docker compose exec app php bin/console doctrine:fixtures:load --no-interaction
	docker compose exec app php bin/console app:import-products
	@echo ""
	@echo "✅ Setup complete! Backend running at http://localhost:8080"
	@echo "   Frontend running at http://localhost:5173"
	@echo "   RabbitMQ management: http://localhost:15672 (guest/guest)"
	@echo "   Demo user: demo@example.com / demo1234"

## Run tests (inside the app container)
test:
	docker compose exec app composer test

## Setup command for deployment in Railway
deploy: composer-install jwt-keys migrate seed import-products