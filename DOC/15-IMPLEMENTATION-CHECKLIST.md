# BookResa — Implementation Checklist

**Backend implementation progress: platform control and customer intelligence are now included in the implemented scope; production-only gates remain environment-dependent.**

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
- [x] Verified current CI baseline (409 passed / 1 skipped / 1926 assertions on 2026-10-20; [GitHub Actions run 39357759308](https://github.com/mazensabry2712/bookresa/actions/runs/39357759308), commit `44b7f6bbc07ff0601dd6fce9914de9412f3c7451`)

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
- [x] public booking payment choice (cash at center always; full online payment when enabled)
- [x] selected payment method persisted on new public bookings and server-validated
- [x] signed merchant return + server-side verification
- [x] operation-aware webhook state mapping and HTTP 200/409 acknowledgements
- [x] webhook idempotency keyed by operation as well as transaction/status to avoid cross-operation collisions
- [x] server-side verification matches merchant, order reference, amount and currency before applying provider state
- [ ] real Kashier sandbox end-to-end verification
- [ ] existing-booking payment retry after failed/expired checkout
- [ ] abandoned pending booking expiry/release lifecycle

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
- [x] social image (public/logo.png configured in shared SEO metadata)
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

## Platform Admin / SaaS Super Admin
- [x] platform admin dashboard
- [x] cross-workspace Super Admin access
- [x] businesses/workspaces list and search
- [x] Workspace Overview with owner, members, subscription, usage, operational quick links and direct status controls
- [x] Platform Workspace CRUD (create/edit/delete) and lifecycle controls
- [x] Platform workspace member management (add, role, owner transfer, suspend, remove)
- [x] business suspension/activation
- [x] subscriptions list
- [x] payments list
- [x] usage periods list
- [x] platform users management
- [x] cross-workspace Customer Intelligence
- [x] customer search, workspace filtering and objective customer signals
- [x] currency-safe customer spending ranking
- [x] Platform Command Center metrics and currency-separated revenue view
- [x] Workspace 360 operational and security activity view
- [x] secure platform impersonation with audited start/stop
- [x] platform security posture view and 2FA status
- [x] platform Activity Log console with workspace/admin/date filters
- [x] support management
- [x] platform settings
- [x] flexible Platform Admin roles with custom permissions
- [x] platform user security controls
- [x] platform payment verification and refund operations
- [x] customer editing and VIP controls
- [x] reversible workspace archive/restore
- [x] two-way support conversations
- [x] platform broadcast center
- [x] platform system health center
- [x] audit CSV export
- [x] global module catalog controls

## Reports
- [x] business reports
- [x] platform reports

## Release gate

The items below are release verification tasks. A previously green baseline does not stay green automatically after code changes.
- [x] full test suite (409 passed / 1 skipped / 1926 assertions on 2026-10-20; [CI run](https://github.com/mazensabry2712/bookresa/actions/runs/39357759308))
- [x] static analysis (PHPStan passed on commit `44b7f6bbc07ff0601dd6fce9914de9412f3c7451`)
- [ ] Pint (full repository currently has 101 style issues across 301 files; changed-file CI checks pass)
- [x] production build
- [x] tenant isolation green (covered by automated suite)
- [ ] payment sandbox green
- [ ] mobile booking QA
- [ ] Arabic/English QA
- [ ] RTL/LTR QA
- [ ] dark/light QA
- [x] SEO QA (automated metadata/sitemap/robots checks)
