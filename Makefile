.PHONY: docker-test docker-verify

COMPOSE = docker compose -f docker/docker-compose.yml

docker-test:
	$(COMPOSE) run --rm package vendor/bin/phpunit

docker-verify:
	$(COMPOSE) run --rm verify
