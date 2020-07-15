SHELL=/bin/bash

UID := $(shell id -u)

install:
	docker run --rm -it -v ${PWD}:/app -u ${UID}:${UID} composer install --no-interaction --optimize-autoloader --ignore-platform-reqs
	env UID=${UID} docker-compose restart php-fpm

up:
	env UID=${UID} docker-compose up -d --build --remove-orphans --force-recreate

up-mac:
	env UID=${UID} docker-sync start;\
	env UID=${UID} docker-compose -f docker-compose.yml -f docker-compose.mac.yml up -d --build --remove-orphans --force-recreate

bash:
	env UID=${UID} docker-compose exec -u app php-fpm bash

api:
	docker run --rm -ti -v ${PWD}:/docs -u ${UID}:${UID} --entrypoint "" humangeo/aglio aglio -i ./blueprint/index.apib --theme-template triple -o api.html

update:
	docker run --rm -it -v ${PWD}:/app -u ${UID}:${UID} composer update --no-interaction --optimize-autoloader --ignore-platform-reqs
	env UID=${UID} docker-compose restart php-fpm

api:
	docker run --rm -ti -v ${PWD}:/docs -u ${UID}:${UID} --entrypoint "" humangeo/aglio aglio -i ./blueprint/index.apib --theme-template triple -o api.html
