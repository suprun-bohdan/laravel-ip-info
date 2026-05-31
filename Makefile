.PHONY: bench docker-test docker-verify docker-stress

COMPOSE = docker compose -f docker/docker-compose.yml

bench:
	composer install --no-interaction --prefer-dist
	php bench/run.php

docker-test:
	$(COMPOSE) run --rm package vendor/bin/phpunit

docker-verify:
	$(COMPOSE) run --rm verify

docker-stress:
	$(COMPOSE) run --rm stress
