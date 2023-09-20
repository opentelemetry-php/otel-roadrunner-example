all: build update start
build:
	docker compose build php
update:
	docker compose run php composer update
start:
	docker compose up -d
stop:
	docker compose stop
bash:
	docker compose run php bash
