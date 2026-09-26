# BookResa — Project Documentation

BookResa is a multi-tenant SaaS platform for small and medium-sized service businesses.

## Technical baseline
- Laravel 13
- PHP 8.4 target
- MySQL 8.x
- Blade
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
17. 16-NOTIFICATIONS.md — notification architecture and delivery rules.
18. 17-PRODUCTION-READINESS.md — production verification and release gate.
19. 18-FRONTEND-DESIGN-IMPLEMENTATION.md — frontend visual direction, UX rules, reference products, implementation order and quality bar.
20. 19-KASHIER-SANDBOX-RUNBOOK.md — Kashier sandbox verification runbook.

## Frontend direction

The frontend is intentionally Blade-first with Tailwind CSS and a small amount of JavaScript.

The visual direction is based on the BookResa brand reference:
- Navy #1E2A44
- Indigo #6366F1
- Coral #FF7A66
- Soft neutral #EDEFF6
- Slate #4B5563
- Plus Jakarta Sans
- Calendar + B icon concept

Reference products are used for UX study, not visual cloning. The goal is a calm, fast and practical booking product with strong mobile behavior, real Arabic RTL support, light/dark themes and minimal frontend dependencies.

## Package policy

Runtime dependencies are intentionally minimal.

Development should avoid adding frontend frameworks or UI kits unless there is a concrete repeated need that cannot be handled cleanly with the current Blade/Tailwind approach.

## Source of truth

This DOC folder is the source of truth for the intended BookResa product, architecture and frontend direction.
