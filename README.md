# FengShui App - Server Deployment Guide

This document outlines the steps to prepare and deploy the FengShui application to a production server.

## 1. Prerequisites

- PHP 8.2 or 8.4
- Node.js (Latest LTS)
- SQLite, MySQL, or PostgreSQL
- Redis (Recommended for queues and cache)

## 2. Server Setup

### Environment Variables
Copy `.env.example` to `.env` and configure the following:
- `APP_KEY`: Generate using `php artisan key:generate`
- `DB_*`: Set your database credentials
- `QUEUE_CONNECTION`: Set to `database` or `redis`
- `ANALYSIS_SERVICE_URL`: URL to the Python Analysis Service (FastAPI)
- `ANALYSIS_SERVICE_TOKEN`: Secret token for API communication
- `INVOICE_NINJA_*`: Credentials for Invoice Ninja integration

### File Permissions
Ensure the following directories are writable by the web server:
- `storage`
- `bootstrap/cache`

## 3. Deployment Steps

Run these commands on the server:

```bash
# 1. Install PHP dependencies
composer install --no-dev --optimize-autoloader

# 2. Install Node dependencies and build assets
npm install
npm run build

# 3. Run migrations
php artisan migrate --force

# 4. Clear and cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 5. Link storage
php artisan storage:link
```

## 4. Background Workers

The application uses background jobs for floor plan analysis. Ensure a queue worker is running:

```bash
php artisan queue:work --tries=3 --timeout=90
```

## 5. Security Checklist

- [ ] `APP_DEBUG` is set to `false`.
- [ ] `APP_ENV` is set to `production`.
- [ ] API tokens are configured for external services.
- [ ] PII (Personally Identifiable Information) encryption is active (check `Customer` and `FamilyMember` models).
- [ ] Two-Factor Authentication is configured via Fortify.

## 6. Python Analysis Service

The FengShui app requires a companion Python service for floor plan analysis.
Ensure the service is running and accessible at the URL defined in `ANALYSIS_SERVICE_URL`.
