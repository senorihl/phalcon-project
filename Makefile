# Environment
DEV_UID:=$(shell id -u)
# BUILDKIT_PROGRESS=plain
export DEV_UID
# export BUILDKIT_PROGRESS

# Executables (local)
DOCKER_COMP = docker compose

# Misc
.DEFAULT_GOAL = help
.PHONY        : help build up start down logs sh composer vendor sf cc test

## —— 🎵 🐳 The Symfony Docker Makefile 🐳 🎵 ——————————————————————————————————
help: ## Outputs this help screen
	@grep -E '(^[a-zA-Z0-9\./_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}{printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}' | sed -e 's/\[32m##/[33m/'

## —— Docker 🐳 ————————————————————————————————————————————————————————————————
build: ## Builds the Docker images
	@$(eval c ?=)
	@$(DOCKER_COMP) build --pull $(c)

up: ## Start the docker hub in detached mode (no logs)
	@$(DOCKER_COMP) up --detach --wait --remove-orphans

start: build up ## Build and start the containers

down: ## Stop the docker hub
	@$(eval opts ?=--remove-orphans)
	@$(DOCKER_COMP) down $(opts)

logs: ## Show live logs
	@$(DOCKER_COMP) logs --tail=0 --follow

assets: ## Connect to the assets builder container
	@$(DOCKER_COMP) exec -e NODE_DEV_UID=$(DEV_UID) -u $(DEV_UID) assets bash

sh: ## Connect to the Phalcon container
	@$(DOCKER_COMP) exec -e DEV_UID=$(DEV_UID) -u www-data php sh

bash: ## Connect to the Phalcon container via bash so up and down arrows go to previous commands
	@$(DOCKER_COMP) exec -e DEV_UID=$(DEV_UID) -u www-data php bash

exec:
	@$(eval u ?= www-data)
	@$(eval c ?=)
	@$(DOCKER_COMP) exec -e DEV_UID=$(DEV_UID) -u $(u) php $(c)

run:
	@$(eval u ?= www-data)
	@$(eval c ?=)
	@$(DOCKER_COMP) run -e DEV_UID=$(DEV_UID) -u $(u) php $(c)

root@sh: ## Connect to the Phalcon container as root
	@$(DOCKER_COMP) run --rm -e DEV_UID=$(DEV_UID) -u root php sh

root@bash: ## Connect to the Phalcon container via bash so up and down arrows go to previous commands as root
	@$(DOCKER_COMP) run --rm -e DEV_UID=$(DEV_UID) -u root php bash

test: ## Start tests with phpunit, pass the parameter "c=" to add options to phpunit, example: make test c="--group e2e --stop-on-failure"
	@$(eval cwd ?= /var/www/app)
	@$(eval c ?=)
	@$(DOCKER_COMP) run -e DEV_UID=$(DEV_UID) -e APP_ENV=test -w $(cwd) php php /var/www/app/vendor/bin/phpunit $(c)


## —— Composer 🧙 ——————————————————————————————————————————————————————————————
composer: ## Run composer, pass the parameter "c=" to run a given command, example: make composer c='req symfony/var-dumper'
	@$(eval c ?=)
	@$(DOCKER_COMP) exec -e APP_ENV=test -u www-data php php /usr/local/bin/composer $(c)

vendor: ## Install vendors according to the current composer.lock file
vendor: c=install --prefer-dist --no-dev --no-progress --no-scripts --no-interaction
vendor: composer