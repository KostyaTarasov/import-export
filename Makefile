DC := docker compose
TOOLS := $(DC) run --rm --user $(shell id -u):$(shell id -g) php

.PHONY: init up down build logs sh install test lint fix

init: .env build up

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

install: backend/vendor/autoload.php

backend/vendor/autoload.php: backend/composer.lock
	$(TOOLS) composer install

test: install
	$(TOOLS) composer test

lint: install
	$(TOOLS) composer lint

fix: install
	$(TOOLS) composer fix
