# BookResa — Implementation Checklist

**Backend implementation progress: 111/122 (91.0%).**

This checklist measures the current backend/product implementation scope, not the frontend rollout. Frontend execution is governed by DOC/18. Production readiness is governed by DOC/17.

## Foundation
- [x] Confirm Herd PHP 8.4
- [x] Align Composer PHP constraint to target
- [x] Configure MySQL
- [x] Define timezone policy
- [x] Arabic/English locale (middleware + browser/session resolution + EN/AR dictionaries)
- [x] RTL/LTR foundation (direction-safe document roots + shared JS fallback)
- [x] Light/Dark foundation (dark class + system preference + persisted theme API)
- [x] Blade design tokens (central brand + semantic CSS variables)
- [x] Previously verified test baseline (221 passed / 1 skipped / 806 assertions on 2026-09-26; rerun required after later hardening)

## Identity/Tenancy
- [x] Fortify
- [x] users
- [x] tenants
- [x] memberships
- [x] TenantResolver/context
- [x] tenant-aware authorization (middleware/context/scopes/services/tests)
- [x] cross-tenant tests
- [x] email verification (Fortify + verified middleware + verification tests)

## RBAC
- [x] Spatie Permission
- [x] Platform Admin
- [x] Owner/Manager/Receptionist/Staff
- [x] Permissions
- [x] Module-aware access
- [x] Negative authorization tests

## Business
- [x] business types
- [x] business profile
- [x] modules
- [x] tenant modules
- [x] localized content
- [x] public slug

## Scheduling
- [x] services
- [x] staff profiles
- [x] service_staff
- [x] business hours
- [x] breaks
- [x] holidays
- [x] special hours
- [x] staff hours
- [x] days off
- [x] availability
- [x] tenant-facing scheduling management

## Booking
- [x] customers
- [x] customer management list/create/update/history
- [x] public booking
- [x] availability engine
- [x] booking lifecycle
- [x] reference
- [x] management list/detail/status actions
- [x] monthly calendar view
- [x] concurrency protection

## Payments
- [x] PaymentGateway contract
- [x] PaymentService
- [x] payments
- [x] Kashier adapter
- [x] secure checkout/session
- [x] signed webhook
- [x] idempotency
- [x] gateway tests
- [x] public booking checkout
- [x] signed merchant return + server-side verification
- [ ] real Kashier sandbox end-to-end verification

## Billing
- [x] plans
- [x] plan modules
- [x] subscriptions
- [x] pricing snapshots
- [x] usage periods/charges
- [x] upgrade/downgrade policy
- [x] owner usage dashboard
- [x] platform plan CRUD and activation controls
- [x] renewal/expiry core
- [x] admin pricing
- [x] subscription checkout/payment flow

## Notifications
- [x] confirmations
- [x] cancellations
- [x] reschedules
- [x] reminders
- [x] payment alerts
- [x] subscription expiry
- [x] usage warnings
- [x] queue delivery

## SEO
- [x] SEO helper/component
- [x] title/description
- [x] canonical
- [x] hreflang (locale query variants for public booking URLs)
- [x] Open Graph
- [ ] social image (waiting for configured public image source)
- [x] JSON-LD
- [x] sitemap
- [x] robots
- [x] noindex private areas

## Performance/Security
- [x] query/index review
- [x] no N+1
- [x] pagination
- [x] stable-data cache
- [x] queue heavy work
- [ ] Redis production
- [ ] OPcache
- [x] rate limits
- [x] audit logs
- [ ] backup/restore test
- [x] production build

## Platform Admin
- [x] platform admin dashboard
- [x] businesses list/search
- [x] business suspension/activation
- [x] subscriptions list
- [x] payments list
- [x] usage periods list
- [x] platform users management
- [x] support management
- [x] platform settings

## Reports
- [x] business reports
- [x] platform reports

## Release gate

The items below are release verification tasks. A previously green baseline does not stay green automatically after code changes.
- [ ] full test suite (previously 221 passed / 1 skipped / 806 assertions; rerun required after current hardening)
- [ ] static analysis (PHPStan previously 0 errors; rerun required after current hardening)
- [ ] Pint (full repository: 96 existing style issues across 267 files; changed-file CI checks pass)
- [x] production build
- [x] tenant isolation green (covered by automated suite)
- [ ] payment sandbox green
- [ ] mobile booking QA
- [ ] Arabic/English QA
- [ ] RTL/LTR QA
- [ ] dark/light QA
- [x] SEO QA (automated metadata/sitemap/robots checks)
