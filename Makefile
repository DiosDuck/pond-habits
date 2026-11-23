# Single entry point for all work (ADR 0006). Run "make help" for the list.
SHELL := /bin/bash

# The containers run with the host UID and GID, so that files in the repo belong to you.
HOST_UID := $(shell id -u)
HOST_GID := $(shell id -g)
export HOST_UID HOST_GID

COMPOSE := docker compose
# Run a command in a new api container. It starts db and mailpit when necessary.
PHP := $(COMPOSE) run --rm -e COMPOSER_HOME=/tmp/composer api
# Run a command in a new web container, with no other services.
NODE := $(COMPOSE) run --rm --no-deps -e npm_config_update_notifier=false web
# Run a command in the Playwright image (Chromium, WebKit). It needs the running stack.
E2E := $(COMPOSE) run --rm e2e

# Tool images. Renovate updates these lines (.github/renovate.json5).
GITLEAKS_IMAGE := ghcr.io/gitleaks/gitleaks:v8.24.0
ACTIONLINT_IMAGE := rhysd/actionlint:1.7.12
export GITLEAKS_IMAGE

.DEFAULT_GOAL := help
.PHONY: help setup up down ps logs build shell migrate seed api-spec \
	test test-backend test-frontend lint lint-backend lint-frontend fix check check-fast \
	build-frontend e2e lighthouse mutation hooks hook-pre-commit lint-repo test-guard deps-check \
	install install-backend install-frontend audit licence-check secrets-scan selftest images \
	mutation-backend mutation-frontend

help: ## Show this list
	@grep -hE '^[a-z0-9-]+:.*## ' $(MAKEFILE_LIST) | awk -F':.*## ' '{printf "  %-14s %s\n", $$1, $$2}'

## Stack

setup: ## First run after a clone: install the packages, make backend/.env, migrate
	$(COMPOSE) build
	$(MAKE) install
	$(PHP) php artisan migrate --no-interaction
	$(MAKE) hooks

install: install-backend install-frontend ## Install the packages from the lockfiles (CI runs this)

install-backend:
	$(PHP) composer install --no-interaction --no-progress
	test -f backend/.env || (cp backend/.env.example backend/.env && $(PHP) php artisan key:generate)

install-frontend:
	$(NODE) npm ci --no-audit --no-fund

hooks: ## Turn on the git hooks in .githooks/ (pre-commit, commit-msg, pre-push)
	git config core.hooksPath .githooks

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

## Images

images: ## Build the prod images (api, web) for this machine, then run the smoke test
	docker build --target prod -t pond-habits-api:local backend
	docker build -f infra/caddy/Dockerfile -t pond-habits-web:local .
	bash scripts/image-smoke.sh pond-habits-api:local pond-habits-web:local

## Quality

test: test-backend test-frontend ## Run all tests with coverage (80% or more)

test-backend:
	$(PHP) ./vendor/bin/pest --coverage --min=80

test-frontend:
	$(NODE) npm run -s test

lint: lint-backend lint-frontend lint-repo ## Run all format checks and static analysis

lint-backend:
	$(PHP) sh -c 'composer validate --strict --no-check-publish \
		&& ./vendor/bin/pint --test \
		&& ./vendor/bin/phpstan analyse --no-progress --memory-limit=1G \
		&& ./vendor/bin/rector process --dry-run --no-progress-bar \
		&& ./vendor/bin/composer-unused --no-progress \
		&& ./vendor/bin/composer-require-checker check'

lint-frontend:
	$(NODE) sh -c 'npm run -s typecheck \
		&& npm run -s lint \
		&& npm run -s format:check \
		&& npm run -s knip \
		&& npm run -s depcruise'

build-frontend: ## Build the frontend, then check the bundle budget and the PWA fields
	$(NODE) sh -c 'npm run -s build && npm run -s check:build'

e2e: ## Run the end-to-end tests (mobile viewports, axe) against the running stack
	$(COMPOSE) up -d --wait
	$(E2E) npx playwright test

lighthouse: build-frontend ## Run Lighthouse on the production build (http://localhost:8081)
	$(COMPOSE) up -d --wait
	$(E2E) sh -c 'CHROME_PATH=$$(ls -d /ms-playwright/chromium-*/chrome-linux*/chrome | head -1) npx lhci autorun'

mutation: mutation-backend mutation-frontend ## Run the mutation tests (60% or more). CI runs them nightly.

mutation-backend:
	$(PHP) ./vendor/bin/pest --mutate --everything --min=60

mutation-frontend:
	$(NODE) npm run -s mutation

lint-repo: ## Check the docs and the repo: line limit, links, sensitive data, scenarios, workflows
	bash scripts/md-line-limit.sh
	bash scripts/link-check.sh
	bash scripts/sensitive-check.sh
	bash scripts/docs-check.sh
	bash scripts/actions-pin-check.sh
	docker run --rm --user "$(HOST_UID):$(HOST_GID)" -v "$(CURDIR)":/repo:ro -w /repo \
		$(ACTIONLINT_IMAGE) -no-color

selftest: ## Check that the guard hooks and the check scripts still catch bad input
	bash .claude/hooks/selftest.sh
	bash scripts/selftest.sh

audit: ## Fail on high or critical security issues in the packages (ADR 0010)
	$(PHP) composer audit --locked --no-interaction --abandoned=report
	$(NODE) npm audit --audit-level=high

licence-check: ## Fail on a package licence that is not on the allow-list (ADR 0010)
	$(NODE) node ../scripts/licence-check.mjs

secrets-scan: ## Scan all commits for secrets with gitleaks
	docker run --rm --user "$(HOST_UID):$(HOST_GID)" -e HOME=/tmp -v "$(CURDIR)":/repo:ro \
		$(GITLEAKS_IMAGE) git /repo --no-banner --log-level warn --redact

test-guard: ## Fail if this branch changes an existing test (compared with origin/main)
	bash scripts/test-guard.sh origin/main

deps-check: ## List the new packages on this branch (the PR needs a "Why this package" line)
	bash scripts/new-deps-check.sh origin/main

hook-pre-commit:
	bash scripts/pre-commit-check.sh

# Only files that are changed or new (not committed) are formatted. Committed tests stay as they are.
fix: ## Format the changed files that are not committed yet
	$(PHP) ./vendor/bin/pint --dirty
	files=$$(git ls-files -mo --exclude-standard -- frontend | sed 's#^frontend/##' | grep -E '\.(ts|tsx|js|cjs|json|css|html|md)$$' || true); \
		if [ -n "$$files" ]; then $(NODE) npx prettier --write $$files; fi

check-fast: lint ## The fast checks (the pre-push hook runs this)

check: ## Run all checks
	$(COMPOSE) config --quiet
	$(MAKE) selftest
	$(MAKE) lint
	$(MAKE) audit
	$(MAKE) licence-check
	$(MAKE) test
	$(MAKE) lighthouse
	$(MAKE) e2e
	$(MAKE) images
