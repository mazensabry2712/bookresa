# BookResa — Project Documentation

BookResa is a multi-tenant SaaS platform for small and medium-sized service businesses.

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

## Product model
One platform → many Businesses → one isolated Workspace/Tenant per Business → configurable modules → public booking page → subscription + unique-customer usage billing.

## Documentation
1. 01-PRODUCT-REQUIREMENTS.md
2. 02-ARCHITECTURE.md
3. 03-DATABASE-ERD.md
4. 04-TENANCY-RBAC.md
5. 05-BOOKING-AVAILABILITY.md
6. 06-PAYMENTS.md
7. 07-SUBSCRIPTIONS-USAGE-BILLING.md
8. 08-LOCALIZATION-THEMING-SEO.md
9. 09-PERFORMANCE.md
10. 10-SECURITY.md
11. 11-MVP-ROADMAP.md
12. 12-TECHNICAL-DECISIONS.md
13. 13-TESTING-QA.md
14. 14-DEPLOYMENT.md
15. 15-IMPLEMENTATION-CHECKLIST.md

## Package policy
Runtime dependencies are intentionally minimal. Planned focused packages:
- laravel/fortify
- spatie/laravel-permission
- spatie/laravel-activitylog
- spatie/laravel-sitemap
- intervention/image

Development tooling:
- pestphp/pest
- pestphp/pest-plugin-laravel
- larastan/larastan
- laravel/pint
- barryvdh/laravel-debugbar (development only)

## Repository baseline
The repository is already Laravel 13 and currently declares PHP ^8.3 in Composer. The project target is PHP 8.4, so the local Herd runtime and Composer PHP constraint must be aligned before feature implementation.

## Source of truth
This DOC folder is the source of truth for the intended BookResa product and technical architecture.