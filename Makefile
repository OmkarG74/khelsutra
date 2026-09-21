.PHONY: up down shell artisan composer test db-reset

up:
	docker compose up -d --build

down:
	docker compose down

shell:
	docker compose exec app bash

artisan:
	docker compose exec app php backend/artisan $(cmd)

composer:
	docker compose exec -w /var/www/html/backend app composer $(cmd)

test:
	docker compose exec -w /var/www/html/backend app php artisan test

db-reset:
	docker compose down -v
	docker compose up -d db
	echo "Waiting for db to initialize..."
	sleep 15
	docker compose up -d
