# Setup Guide for Member 4 (Operations & Logistics)

## Run without Docker (default for teammates)

1. Create a database `khelsutra` and `khelsutra_test`.
2. Import the baseline dump:
   ```bash
   mysql -u root -p khelsutra < database/sql/khelsutra.sql
   mysql -u root -p khelsutra_test < database/sql/khelsutra.sql
   ```
3. Install dependencies:
   ```bash
   cd backend
   composer install
   ```
4. Configure `.env`:
   ```bash
   cp ../.env.example .env
   php artisan key:generate
   ```
5. Apply operations migrations:
   ```bash
   php artisan migrate
   ```
6. Serve the application:
   ```bash
   php -S 127.0.0.1:8000 -t public
   ```
7. Run tests:
   ```bash
   php artisan test
   ```

## Run with Docker (optional)

If you prefer a Dockerized environment, ensure Docker is installed.

1. Start the containers:
   ```bash
   make up
   ```
2. Install dependencies:
   ```bash
   make composer cmd="install"
   ```
3. Configure `.env`:
   ```bash
   cp .env.example backend/.env
   make artisan cmd="key:generate"
   ```
4. Run migrations:
   ```bash
   make artisan cmd="migrate"
   ```
5. Run tests:
   ```bash
   make test
   ```

The application will be accessible at `http://localhost:8000` and phpMyAdmin at `http://localhost:8081`.
To reset the database from the baseline dump, run `make db-reset`.
