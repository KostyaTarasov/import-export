DC := docker compose
TOOLS := $(DC) run --rm --user $(shell id -u):$(shell id -g) php

.PHONY: init up down build logs sh migrate seed install test lint fix

init: .env build up migrate seed

.env:
	cp .env.example .env

up:
	$(DC) up -d

down:
	$(DC) down

build:
	$(DC) build

logs:
	$(DC) logs -f app nginx

sh:
	$(DC) exec app sh

migrate:
	$(DC) exec -u www-data app bin/console migrations:migrate --no-interaction

seed:
	$(DC) exec -u www-data app bin/console app:fixtures:load --no-interaction

install: backend/vendor/autoload.php

backend/vendor/autoload.php: backend/composer.lock
	$(TOOLS) composer install

test: install
	$(TOOLS) composer test

lint: install
	$(TOOLS) composer lint

fix: install
	$(TOOLS) composer fix
