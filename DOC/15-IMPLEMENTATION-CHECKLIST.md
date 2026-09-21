# BookResa — Implementation Checklist

## Foundation
- [ ] Confirm Herd PHP 8.4
- [ ] Align Composer PHP constraint to target
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
- [ ] cross-tenant tests

## RBAC
- [ ] Spatie Permission
- [ ] Platform Admin
- [ ] Owner/Manager/Receptionist/Staff
- [ ] Permissions
- [ ] Module-aware access
- [ ] Negative authorization tests

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
- [ ] PaymentGateway
- [ ] PaymentService
- [ ] payments
- [ ] Kashier adapter
- [ ] secure checkout/session
- [ ] signed webhook
- [ ] idempotency
- [ ] gateway tests

## Billing
- [ ] plans
- [ ] plan modules
- [ ] subscriptions
- [ ] pricing snapshots
- [ ] usage periods/charges
- [ ] upgrade/downgrade
- [ ] renewal/expiry
- [ ] admin pricing

## Notifications
- [ ] confirmations
- [ ] cancellations
- [ ] reschedules
- [ ] reminders
- [ ] payment alerts
- [ ] subscription expiry
- [ ] usage warnings
- [ ] queue delivery

## SEO
- [ ] SEO helper/component
- [ ] title/description
- [ ] canonical
- [ ] hreflang
- [ ] Open Graph
- [ ] JSON-LD
- [ ] sitemap
- [ ] robots
- [ ] noindex private areas

## Performance/Security
- [ ] query/index review
- [ ] no N+1
- [ ] pagination
- [ ] stable-data cache
- [ ] queue heavy work
- [ ] Redis production
- [ ] OPcache
- [ ] rate limits
- [ ] audit logs
- [ ] backup/restore test
- [ ] production build

## Release gate
- [ ] full test suite
- [ ] static analysis
- [ ] Pint
- [ ] production build
- [ ] tenant isolation green
- [ ] payment sandbox green
- [ ] mobile booking QA
- [ ] Arabic/English QA
- [ ] RTL/LTR QA
- [ ] dark/light QA
- [ ] SEO QA
