# BookResa — Deployment

## Local
Primary:
- Windows
- Laravel Herd
- PHP 8.4
- MySQL
- optional Redis

No Docker or WSL requirement for MVP development.

Recommended local domain: bookresa.test

## Configuration
APP, DB, SESSION, CACHE, QUEUE, MAIL, FILESYSTEM, KASHIER, locale, timezone and public URL.

Never commit .env secrets.

## Production
Internet → Nginx/compatible web server → PHP/Laravel → MySQL + Redis + queue workers + scheduler + storage.

Enable HTTPS, OPcache, optimized autoloading, production config/cache, worker supervision and backups.

## Queue
Run workers continuously under a process supervisor.

## Scheduler
Run Laravel scheduler continuously (commonly every minute) for reminders, expiry checks, usage tasks and cleanup.

## Storage
Laravel Filesystem locally; S3-compatible/object storage can be introduced later.

## Backups
At minimum daily database backups, retention, offsite copy and periodic restore tests.

## Deployment sequence
Deploy reviewed release → install optimized Composer dependencies → build assets → run migrations safely → cache production config/routes/views as appropriate → restart workers → verify health/queues/scheduler → monitor logs.

## Scaling
Start with one application + MySQL. Add Redis/queue capacity/external storage. Scale app/database only when measurements justify it. Consider Octane/FrankenPHP after benchmarking.
