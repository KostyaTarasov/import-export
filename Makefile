DC := docker compose
USER_ID := $(shell id -u):$(shell id -g)
PHP := $(DC) run --rm --user $(USER_ID) php
NODE := $(DC) run --rm --no-deps --user $(USER_ID) node
E2E := $(DC) -p product-import-e2e -f docker-compose.yml -f docker-compose.e2e.yml

.PHONY: init up down build logs sh migrate seed install dev test lint fix e2e

init: .env build up migrate seed

.env:
	cp .env.example .env
	sed -i.bak "s/^JWT_SECRET=$$/JWT_SECRET=$$(openssl rand -hex 32)/" .env && rm .env.bak

up:
	$(DC) up -d

down:
	$(DC) down

build:
	$(DC) build

logs:
	$(DC) logs -f app worker nginx

sh:
	$(DC) exec app sh

migrate:
	$(DC) exec -u www-data app bin/console migrations:migrate --no-interaction

seed:
	$(DC) exec -u www-data app bin/console app:fixtures:load --no-interaction

install: backend/vendor/autoload.php frontend/node_modules/.package-lock.json

backend/vendor/autoload.php: backend/composer.lock
	$(PHP) composer install

frontend/node_modules/.package-lock.json: frontend/package-lock.json
	$(NODE) npm ci

dev: install
	$(DC) run --rm --service-ports --user $(USER_ID) node npx ng serve --host 0.0.0.0 --port 4200

test: install
	$(PHP) composer test
	$(NODE) npx ng test --watch=false

lint: install
	$(PHP) composer lint
	$(NODE) sh -c 'npx ng lint && npm run format:check'

fix: install
	$(PHP) composer fix
	$(NODE) npm run format

e2e: .env frontend/node_modules/.package-lock.json
	$(E2E) up -d --build --wait
	$(E2E) exec -T -u www-data app bin/console migrations:migrate --no-interaction
	$(E2E) exec -T -u www-data app bin/console app:fixtures:load --no-interaction
	$(E2E) run --rm --user $(USER_ID) playwright; status=$$?; $(E2E) down -v; exit $$status
