# BookResa — Deployment

## Local development
Primary:
- Windows
- Laravel Herd
- PHP 8.4
- MySQL
- optional Redis

No Docker/WSL requirement for MVP development.

Recommended local domain: bookresa.test

## Configuration
APP, DB, SESSION, CACHE, QUEUE, MAIL, FILESYSTEM, KASHIER/payment settings, locale, timezone and public URL.

Never commit .env secrets.

## Production
Internet → Nginx/compatible web server → PHP/Laravel → MySQL + Redis + queue workers + scheduler + storage.

Enable HTTPS, OPcache, optimized Composer autoloading, production configuration/cache, worker supervision and backups.

## Queue
Run workers continuously under a process supervisor.

## Scheduler
Run Laravel Scheduler continuously (commonly every minute) for reminders, expiry checks, usage jobs and cleanup.

## Storage
Use Laravel Filesystem. Move to S3-compatible/object storage when scale requires it.

## Backups
At least daily database backups with retention, offsite copy and periodic restore testing.

## Deployment sequence
1. Deploy reviewed release.
2. Install optimized Composer dependencies.
3. Build frontend assets.
4. Run migrations safely.
5. Cache production config/routes/views where appropriate.
6. Restart workers.
7. Verify health, queues and scheduler.
8. Monitor logs.

## Scaling
Start with one application + MySQL. Add Redis/queue capacity/external storage, then scale application/database based on measurements. Consider Octane/FrankenPHP only after benchmarking.
