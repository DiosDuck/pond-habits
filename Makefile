# Single entry point for all work (ADR 0006). Run "make help" for the list.
SHELL := /bin/bash

# The containers run with the host UID and GID, so that files in the repo belong to you.
HOST_UID := $(shell id -u)
HOST_GID := $(shell id -g)
export HOST_UID HOST_GID

COMPOSE := docker compose
# Run a command in a new api container. It starts db and mailpit when necessary.
PHP := $(COMPOSE) run --rm -e COMPOSER_HOME=/tmp/composer api

.DEFAULT_GOAL := help
.PHONY: help up down ps logs build shell migrate seed api-spec \
	test lint lint-backend fix check check-fast

help: ## Show this list
	@grep -hE '^[a-z-]+:.*## ' $(MAKEFILE_LIST) | awk -F':.*## ' '{printf "  %-12s %s\n", $$1, $$2}'

## Stack

up: ## Build and start the local stack, then wait until it is ready
	$(COMPOSE) up -d --build --wait

down: ## Stop the local stack (the data stays)
	$(COMPOSE) down

ps: ## Show the containers
	$(COMPOSE) ps

logs: ## Follow the logs of all containers
	$(COMPOSE) logs -f --tail=100

build: ## Build the images again with no cache
	$(COMPOSE) build --no-cache

shell: ## Open a shell in a new api container
	$(PHP) sh

## Backend

migrate: ## Run the database migrations
	$(PHP) php artisan migrate

seed: ## Fill the local database with demo data
	$(PHP) php artisan db:seed

api-spec: ## Write the OpenAPI spec to docs/generated/openapi.json (ADR 0021)
	mkdir -p docs/generated
	$(PHP) php artisan scramble:export --path=../docs/generated/openapi.json

# "make admin" comes with the accounts feature (Phase 3).

## Quality

test: ## Run the backend tests with coverage (80% or more)
	$(PHP) ./vendor/bin/pest --coverage --min=80

lint: lint-backend ## Run all format checks and static analysis

lint-backend:
	$(PHP) sh -c 'composer validate --strict --no-check-publish \
		&& ./vendor/bin/pint --test \
		&& ./vendor/bin/phpstan analyse --no-progress --memory-limit=1G \
		&& ./vendor/bin/rector process --dry-run --no-progress-bar \
		&& ./vendor/bin/composer-unused --no-progress \
		&& ./vendor/bin/composer-require-checker check'

fix: ## Format the changed files that are not committed yet (committed tests stay as they are)
	$(PHP) ./vendor/bin/pint --dirty

check-fast: lint ## The fast checks (the pre-push hook runs this)

check: ## Run all checks
	$(COMPOSE) config --quiet
	bash .claude/hooks/selftest.sh
	$(MAKE) lint
	$(MAKE) test
