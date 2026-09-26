# BookResa — Implementation Checklist

## Foundation
- [ ] Confirm Herd PHP 8.4
- [x] Align Composer PHP constraint to target
- [ ] Configure MySQL
- [ ] Define timezone policy
- [ ] Arabic/English locale
- [ ] RTL/LTR foundation
- [ ] Light/Dark foundation
- [ ] Blade design tokens
- [ ] Testing baseline

## Identity/Tenancy
- [ ] Fortify
- [ ] users
- [ ] tenants
- [ ] memberships
- [ ] TenantResolver/context
- [ ] tenant-aware policies
- [x] cross-tenant tests

## RBAC
- [ ] Spatie Permission
- [x] Platform Admin
- [x] Owner/Manager/Receptionist/Staff
- [x] Permissions
- [x] Module-aware access
- [x] Negative authorization tests

## Business
- [x] business types
- [x] business profile
- [x] modules
- [x] tenant modules (core access enforced; admin UI pending)
- [x] localized content
- [ ] public slug

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
- [ ] hreflang (pending locale-prefixed public URLs)
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
- [ ] support management
- [ ] platform settings

## Reports
- [ ] business reports
- [ ] platform reports

## Release gate
- [ ] full test suite (refresh after latest backend additions)
- [ ] static analysis
- [x] Pint
- [x] production build
- [x] tenant isolation green (covered by automated suite)
- [ ] payment sandbox green
- [ ] mobile booking QA
- [ ] Arabic/English QA
- [ ] RTL/LTR QA
- [ ] dark/light QA
- [x] SEO QA (automated metadata/sitemap/robots checks)
