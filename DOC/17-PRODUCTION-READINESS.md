# BookResa — Production Readiness Runbook

## Current verified local gate

The latest local verification reported on 2026-09-26:
- full test suite: 240 passed, 1 skipped, 883 assertions
- `php artisan view:cache`: passed
- Vite production build: passed
- `composer install` and optimized autoload generation: passed
- `php artisan optimize:clear`: passed

The latest locally verified application commit is `7b68bbb`; GitHub Actions status for the subsequent verification commits should be checked before release.

Pint: touched files pass; the full repository currently has 104 existing style issues across 290 files
- SEO regression tests: previously verified
- PHP 8.4 is the project target
- Larastan is declared in Composer and locked on `main`, so CI can enforce PHPStan

The CI workflow in `.github/workflows/ci.yml` runs Composer validation/install, PHPStan, syntax, changed-file Pint, frontend build and the full test suite.

## Production environment

Use PHP 8.4 in production.

Recommended production environment values:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.example

SESSION_DRIVER=redis
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax

CACHE_STORE=redis

QUEUE_CONNECTION=redis
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=change-me

MAIL_MAILER=...
FILESYSTEM_DISK=...
```

Keep the existing local Herd configuration simple. Production should use Redis for cache, queues, rate limiting and distributed locks.

Never commit real secrets. Set Kashier credentials and other secrets through the deployment environment.

## Deploy sequence

From a clean production checkout:

```bash
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
npm ci
npm run build

php artisan migrate --force
php artisan storage:link

php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan optimize
```

Run database migrations before routing traffic to code that depends on the new schema.

## Queue workers

Run supervised queue workers in production:

```bash
php artisan queue:work redis --sleep=1 --tries=3 --timeout=120
```

Use a process supervisor such as systemd, Supervisor, or the hosting platform's process manager. Restart workers after deployments so they load the new application code.

The scheduler must also run continuously:

```bash
php artisan schedule:run
```

Configure the host to invoke that command every minute.

## Redis

Use separate logical Redis databases/connections for application cache and queue data where appropriate. Keep Redis private to the application network and require authentication when the deployment architecture supports it.

Do not make live availability authoritative from Redis cache. Availability must continue to read current booking state.

## OPcache

Production PHP should have OPcache enabled. A typical baseline is:

```ini
opcache.enable=1
opcache.enable_cli=0
opcache.validate_timestamps=0
opcache.memory_consumption=192
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=20000
opcache.save_comments=1
```

With timestamp validation disabled, restart PHP-FPM or the application runtime after every deployment.

Tune OPcache using production measurements rather than blindly increasing memory values.

## MySQL backups

Create a logical backup before every risky migration or release:

```bash
mysqldump --single-transaction --routines --triggers --events \
  -h "$DB_HOST" -P "$DB_PORT" \
  -u "$DB_USERNAME" -p "$DB_DATABASE" \
  > "bookresa-$(date +%Y%m%d-%H%M%S).sql"
```

Store backups outside the application server when possible. Encrypt backup storage when supported by the infrastructure.

## Restore test

A backup is not considered verified until it has been restored into a separate staging database.

Example:

```bash
mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" -p \
  -e "CREATE DATABASE bookresa_restore CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"

mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" -p bookresa_restore \
  < bookresa-YYYYMMDD-HHMMSS.sql
```

Then run the application against the restored database and verify authentication, tenant resolution, booking lookup, billing records and notifications.

Delete the temporary restore database after verification if it is no longer needed.

The restore procedure must be executed against a real staging backup before the checklist item is marked complete.

## Release gate

Required before production traffic:
- CI green
- full local test suite green
- Pint green
- production frontend build green
- tenant isolation tests green
- payment sandbox verification complete
- backup/restore drill complete
- production HTTPS and security headers verified
- Redis/cache/queue workers healthy
- scheduler healthy
- OPcache enabled
- monitoring and error logs visible

Do not mark a production item complete only because configuration files exist; verify the deployed infrastructure itself.
