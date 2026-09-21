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
1. 00-PROJECT-SPECIFICATION.md — canonical complete specification.
2. 01-PRODUCT-REQUIREMENTS.md
3. 02-ARCHITECTURE.md
4. 03-DATABASE-ERD.md
5. 04-TENANCY-RBAC.md
6. 05-BOOKING-AVAILABILITY.md
7. 06-PAYMENTS.md
8. 07-SUBSCRIPTIONS-USAGE-BILLING.md
9. 08-LOCALIZATION-THEMING-SEO.md
10. 09-PERFORMANCE.md
11. 10-SECURITY.md
12. 11-MVP-ROADMAP.md
13. 12-TECHNICAL-DECISIONS.md
14. 13-TESTING-QA.md
15. 14-DEPLOYMENT.md
16. 15-IMPLEMENTATION-CHECKLIST.md

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