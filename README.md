# Delta IT — Laravel Backend 🛡️

A production-ready backend for the Delta IT application built with Laravel 11. This repository contains the server-side API, models, business logic, authentication, and integrations used by the web and mobile clients.

---

## Description

Delta IT backend provides a comprehensive meeting-room and company management platform offering:
- Authentication & user management (JWT + session-based)
- Role & permission management (Spatie)
- Company, contract, document management
- Meeting, booking, and event scheduling with request/approval flows
- Tickets (support), notifications, payments and reporting (dashboard stats)
- File uploads and image processing

This README documents how to run the backend locally, connect databases, run migrations and seeders, and prepare the app for production.

---

## Demo

No public demo is provided in the repository. To preview locally, follow the installation steps below and run the app on http://localhost:8000.

---

## 🛠 Tech Stack

- PHP 8.2+
- Laravel 11
- MySQL / PostgreSQL / SQLite (configurable via `.env`)
- Redis (for caching/queues, optional)
- Tymon JWT Auth — API token authentication
- Spatie Laravel Permission — RBAC (roles & permissions)
- Yajra Datatables — server-side tables
- Intervention Image — image processing
- Laravel Breeze — (optional) auth scaffolding
- Dev tools: Laravel Pint, PHPUnit, Laravel Sail (dev)

---

## ✨ Main Features

- JWT-based mobile/API authentication and session-based web authentication
- Roles & permissions for fine-grained access control
- Company and user account management with file uploads
- Contracts, internal documents, and notes
- Meeting & event creation, booking, approvals, and calendar helpers
- Ticketing system with messages & attachments
- Notification endpoints and read/unread handling
- Payments recording and simple reporting endpoints

(Features inferred from `routes/api.php`, controllers under `App\Http\Controllers\Api`, and database seeders.)

---

## 📂 Project Structure (important folders)

```
├── app/
│   ├── Http/
│   │   ├── Controllers/        # Controller classes (API & web)
│   │   ├── Middleware/
│   │   └── Requests/
│   ├── Jobs/
│   ├── Mail/
│   ├── Models/                 # Eloquent models (User, Company, Meeting, etc.)
│   └── Notifications/
├── bootstrap/
├── config/                     # Config files (auth, jwt, permission, queue)
├── database/
│   ├── migrations/
│   └── seeders/
├── resources/                  # Blade views, frontend assets (Vite)
├── routes/
│   ├── api.php                 # API routes (primary integration points)
│   └── web.php
├── storage/
├── tests/
└── composer.json
```

For a full list of routes and controllers, see `routes/api.php` and `app/Http/Controllers/Api/`.

---

## 🚀 Installation & Setup

### Prerequisites

- PHP 8.2+
- Composer
- A supported database (MySQL/PostgreSQL/SQLite)
- Node.js & npm (for compiling frontend assets if needed)

### 1) Clone the repository

```bash
git clone <repo-url>
cd <repo-folder>
```

### 2) Install PHP dependencies

```bash
composer install
```

### 3) Copy and configure your environment

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` to configure the application and database connection. See **Database configuration** below.

### 4) Create the database & run migrations

After configuring `.env`, run:

```bash
# create database manually depending on your DB engine, then:
php artisan migrate --seed
```

This runs migrations and seeders (the `UserSeeder` creates an initial SuperAdmin user).

### 5) Set up JWT and storage

```bash
# generate JWT secret used for API tokens
php artisan jwt:secret

# create a symbolic link for storage (for public uploads)
php artisan storage:link
```

### 6) Install JS dependencies (optional)

If you plan to modify frontend assets or use Vite for asset building:

```bash
npm install
npm run dev      # or npm run build for production
```

### 7) Serve the application

```bash
php artisan serve --port=8000
# or use your preferred PHP-FPM + web server (Nginx/Apache)
```

---

## 🔌 Database configuration (how to connect your DB)

This project supports multiple DB drivers. Edit the following in `.env` to match your environment:

Common DB variables:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=delta_it
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password
```

MySQL example (local):
1. Create the DB:
   ```sql
   CREATE DATABASE delta_it CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE USER 'delta_user'@'localhost' IDENTIFIED BY 'secret';
   GRANT ALL PRIVILEGES ON delta_it.* TO 'delta_user'@'localhost';
   FLUSH PRIVILEGES;
   ```
2. Update `.env` with those credentials and run migrations:
   ```bash
   php artisan migrate --seed
   ```

SQLite example (fast local setup):
1. Set `.env`:
   ```dotenv
   DB_CONNECTION=sqlite
   DB_DATABASE=${PWD}/database/database.sqlite
   ```
2. Create file and migrate:
   ```bash
   touch database/database.sqlite
   php artisan migrate --seed
   ```

PostgreSQL example: adjust `DB_CONNECTION=pgsql` and provide host/port/username/password.

Important: After changing `.env`, run `php artisan config:clear` and `php artisan cache:clear` if needed.

---

## 📜 Available Commands & Scripts

PHP / Artisan:

- `php artisan serve` — run local dev server
- `php artisan migrate` — run migrations
- `php artisan migrate:fresh --seed` — reset DB and seed
- `php artisan db:seed` — run seeders
- `php artisan jwt:secret` — generate JWT secret
- `php artisan queue:work` — start processing queued jobs
- `php artisan storage:link` — expose storage
- `php artisan test` — run the test suite

Composer scripts (from composer.json):
- `composer install` — install PHP deps

NPM scripts (frontend assets):
- `npm run dev` — start Vite dev server
- `npm run build` — build frontend assets

Linting & QA:
- `vendor/bin/pint` — run Laravel Pint for code style
- `vendor/bin/phpunit` or `php artisan test` — run tests

---

## 🤝 Contributing

Contributions are welcome. Suggested workflow:
1. Fork the repository
2. Create a branch: `feature/your-feature` or `fix/issue-x`
3. Run tests and linters locally
4. Submit a pull request with a clear description and test coverage

Please open issues for feature requests or bugs.

---

## 📄 License

This project uses the **MIT** license (see `composer.json`). If you want a `LICENSE` file added, I can add one.

---

## 🙏 Acknowledgements

- Laravel — framework
- Spatie — permissions
- Tymon JWT Auth — API authentication
- Yajra DataTables — table utilities
- Intervention/Image — image handling
- Laravel Breeze — optional auth scaffolding

---

If you want, I can also:
- Add a `CONTRIBUTING.md` with rules and Git hooks
- Add a `.env.example` section documenting every important env var
- Add a `docker-compose` + Sail setup for easy local development

Tell me which you'd like me to add next and I will implement it.

---

## Frontend Assets (Vite + Tailwind + Alpine) 🔧

This repository also contains a focused frontend asset pipeline used by the Laravel app. These assets are not a separate SPA — they are compiled with Vite and injected into Blade views.

### Brief description
- **Build tool:** Vite (fast HMR, dev server)
- **Styling:** Tailwind CSS (`@tailwindcss/forms` plugin included)
- **Interactions:** Alpine.js
- **HTTP client:** Axios (configured in `resources/js/bootstrap.js`)
- **Vite integration:** `laravel-vite-plugin` configured in `vite.config.js`

### Key files & structure
```
package.json
vite.config.js
resources/
├─ css/app.css        # Tailwind entry
├─ js/app.js          # Vite/JS entry
├─ js/bootstrap.js    # axios + global JS bootstrapping
└─ views/             # Blade components & views using the compiled assets
```

### Installation & Quick Start 🚀
1. Install Node deps:
```bash
npm install
```
2. Start Vite dev server (HMR):
```bash
npm run dev
```
3. Build production assets:
```bash
npm run build
```

To view the full Laravel app locally, make sure you also have PHP & Composer dependencies installed and run `php artisan serve`.

### Available npm scripts
- `npm run dev` — start Vite dev server
- `npm run build` — build production assets

> Note: Linting/preview scripts are not currently configured — I can add ESLint, Prettier, and a `npm run preview` script on request.

---

If you'd like, I can also add a sample `tailwind.config.js`, PostCSS tweaks, or a minimal ESLint + Prettier setup for the frontend assets — tell me which and I'll add them.
