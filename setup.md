# BizPOS Pro - Setup Instructions

## Prerequisites

| Requirement | Version |
|---|---|
| PHP | 8.2+ |
| Composer | 2.x |
| MySQL | 8.0+ |
| Node.js | 18+ |
| npm | 9+ |
| Git | 2.x |

### Required PHP Extensions

- `pdo_mysql`
- `mbstring`
- `openssl`
- `tokenizer`
- `xml`
- `ctype`
- `json`
- `bcmath`
- `gd` or `imagick`
- `zip`

---

## Quick Setup

### 1. Clone the Repository

```bash
git clone <repository-url> bizpos
cd bizpos
```

### 2. Install Dependencies

```bash
composer install
npm install
```

### 3. Environment Configuration

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` and configure database credentials:

```env
APP_NAME="BizPOS Pro"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=bizpos
DB_USERNAME=root
DB_PASSWORD=your_password

SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database
```

### 4. Database Setup

Create the MySQL database:

```sql
CREATE DATABASE bizpos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Run migrations and seeders:

```bash
php artisan migrate
php artisan db:seed
```

### 5. Build Frontend Assets

```bash
npm run build
```

### 6. Start the Development Server

**Option A** - All-in-one (server + queue + logs):

```bash
composer dev
```

This runs concurrently:
- `php artisan serve` (web server on port 8000)
- `php artisan queue:listen` (background jobs)
- `php artisan pail` (log viewer)
- `npm run dev` (Vite for assets)

**Option B** - Manual:

```bash
php artisan serve
```

Visit `http://localhost:8000` in your browser.

---

## One-Command Setup

If all prerequisites are installed:

```bash
composer setup
```

This runs: `composer install` > copy `.env` > `key:generate` > `migrate` > `npm install` > `npm run build`.

---

## Vendor Libraries (Local - No CDN)

All frontend libraries are served locally from `public/vendor/`. They are committed to the repo and do not require separate installation:

| Library | Version | Path |
|---|---|---|
| Bootstrap | 5.3.3 | `public/vendor/bootstrap/` |
| jQuery | 3.7.1 | `public/vendor/jquery/` |
| FontAwesome | 6.5.1 | `public/vendor/fontawesome/` |
| Nunito Sans | latest | `public/vendor/nunito-sans/` |
| Chart.js | 4.4.0 | `public/vendor/chartjs/` |

---

## Key Composer Packages

| Package | Purpose |
|---|---|
| `nwidart/laravel-modules` | Modular architecture (35 modules) |
| `spatie/laravel-permission` | Roles and permissions |
| `maatwebsite/excel` | Excel import/export |
| `barryvdh/laravel-dompdf` | PDF generation |

---

## Module System

BizPOS uses `nwidart/laravel-modules` v12. Modules are located in the `Modules/` directory. Each module is self-contained with its own controllers, models, services, routes, views, and migrations.

To list all modules:

```bash
php artisan module:list
```

To enable/disable a module:

```bash
php artisan module:enable ModuleName
php artisan module:disable ModuleName
```

---

## Mobile App (React Native)

The companion mobile app lives in the sibling directory `../bizpos-mobile/`. It connects to this Laravel backend via REST API (`/api/v1/`).

### Mobile Setup

```bash
cd ../bizpos-mobile
npm install
npx react-native run-android   # or run-ios
```

The API uses Laravel Sanctum for token-based authentication.

---

## Production Deployment

1. Set `APP_ENV=production` and `APP_DEBUG=false` in `.env`
2. Set `APP_URL` to your production domain (HTTPS)
3. Run `php artisan config:cache && php artisan route:cache && php artisan view:cache`
4. Set up a queue worker: `php artisan queue:work --daemon`
5. Configure your web server (Nginx/Apache) to point to `public/`
6. Ensure HTTPS is configured (required for PWA features)
7. Run `php artisan storage:link` for file uploads

---

## Troubleshooting

| Issue | Solution |
|---|---|
| Permission errors on storage/bootstrap | `chmod -R 775 storage bootstrap/cache` |
| Module not found | Run `php artisan module:list` and `composer dump-autoload` |
| Migration errors | Check DB credentials in `.env`, run `php artisan migrate:status` |
| Assets not loading | Run `npm run build` and check `public/vendor/` exists |
| CSRF token mismatch | Clear browser cookies, check `meta[name=csrf-token]` in layout |
| Queue jobs not processing | Start queue worker: `php artisan queue:listen` |
