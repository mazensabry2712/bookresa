# BookResa — Project Documentation

BookResa is the project repository for a multi-tenant Business Booking SaaS Platform.

## Product
BookResa provides one platform where many businesses create isolated Workspaces/Tenants, activate configurable modules, manage services/staff/customers/bookings, publish public booking pages, and pay subscription + usage fees.

## Technical baseline
- Laravel 13
- PHP 8.4 target
- MySQL 8.x
- Blade
- Alpine.js
- Tailwind CSS
- Vite
- Redis-ready cache/queue/lock layer
- OPcache in production
- Modular Monolith
- Shared-database multi-tenancy

## Planned focused packages
Runtime:
- laravel/fortify
- spatie/laravel-permission
- spatie/laravel-activitylog
- spatie/laravel-sitemap
- intervention/image

Development:
- pestphp/pest
- pestphp/pest-plugin-laravel
- larastan/larastan
- laravel/pint
- barryvdh/laravel-debugbar (dev only)

## Documentation map
- 01-PRODUCT-REQUIREMENTS.md
- 02-ARCHITECTURE.md
- 03-DATABASE-ERD.md
- 04-TENANCY-RBAC.md
- 05-BOOKING-AVAILABILITY.md
- 06-PAYMENTS.md
- 07-SUBSCRIPTIONS-USAGE-BILLING.md
- 08-LOCALIZATION-THEMING-SEO.md
- 09-PERFORMANCE.md
- 10-SECURITY.md
- 11-MVP-ROADMAP.md
- 12-TECHNICAL-DECISIONS.md
- 13-TESTING-QA.md
- 14-DEPLOYMENT.md
- 15-IMPLEMENTATION-CHECKLIST.md

## Important repository note
The repository currently declares Laravel 13 and PHP ^8.3 in Composer. The project target is PHP 8.4, so the Composer constraint and Herd runtime must be aligned to PHP 8.4 before implementation.
