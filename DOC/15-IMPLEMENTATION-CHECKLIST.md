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
- [ ] business types
- [ ] business profile
- [ ] modules
- [ ] tenant modules
- [ ] localized content
- [ ] public slug

## Scheduling
- [ ] services
- [ ] staff profiles
- [ ] service_staff
- [ ] business hours
- [ ] breaks
- [ ] holidays
- [ ] special hours
- [ ] staff hours
- [ ] days off
- [ ] availability

## Booking
- [x] customers
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
- [ ] stable-data cache
- [x] queue heavy work
- [ ] Redis production
- [ ] OPcache
- [x] rate limits
- [x] audit logs
- [ ] backup/restore test
- [x] production build

## Release gate
- [x] full test suite (165 passed / 559 assertions locally)
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
