DC ?= docker compose

NODE_IMAGE ?= node:22-alpine
NODE_RUN = docker run --rm -v $(CURDIR)/client:/app -w /app -u $$(id -u):$$(id -g) -e HOME=/tmp $(NODE_IMAGE)

EMAIL ?= user@miractal.com
PASSWORD ?= password

.PHONY: deploy env build client up composer migrate seed-currencies seed-user

deploy: env build client up composer migrate seed-currencies seed-user
	@echo ""
	@echo "Done. App is available at: https://localhost:8888"
	@echo "Demo login: $(EMAIL) / $(PASSWORD)"

env:
	@if [ ! -f .env ]; then cp .env.example .env; echo "Created .env from .env.example"; fi

build:
	$(DC) build

client:
	$(NODE_RUN) npm install

up:
	$(DC) up -d

composer:
	$(DC) exec --user $$(id -u):$$(id -g) -e HOME=/tmp app composer install

migrate:
	$(DC) exec app php console/migrate.php migrate

seed-currencies:
	$(DC) exec app php console/seed-currencies.php

seed-user:
	$(DC) exec app php console/seed-user.php $(EMAIL) $(PASSWORD) || true
