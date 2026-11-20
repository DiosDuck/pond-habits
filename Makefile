# Single entry point for all work (ADR 0006). Run "make help" for the list.
SHELL := /bin/bash

# The containers run with the host UID and GID, so that files in the repo belong to you.
HOST_UID := $(shell id -u)
HOST_GID := $(shell id -g)
export HOST_UID HOST_GID

COMPOSE := docker compose

.DEFAULT_GOAL := help
.PHONY: help up down ps logs build check

help: ## Show this list
	@grep -hE '^[a-z-]+:.*## ' $(MAKEFILE_LIST) | awk -F':.*## ' '{printf "  %-12s %s\n", $$1, $$2}'

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

check: ## Run all checks (more checks come in the next PRs)
	$(COMPOSE) config --quiet
	bash .claude/hooks/selftest.sh
