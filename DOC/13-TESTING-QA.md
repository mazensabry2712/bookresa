# BookResa — Testing and QA

## Tooling
Pest, Larastan and Pint. Debugbar is development-only.

## Unit tests
Slot calculation, usage math, price calculation and status transition rules.

## Feature tests
Authentication, tenant access, CRUD, public booking, billing, payment webhooks, admin permissions and email verification.

## Mandatory tenant tests
- Tenant A cannot read Tenant B customer.
- Tenant A cannot update Tenant B booking.
- Tenant A cannot delete Tenant B service.
- Forged tenant_id cannot switch context.
- Staff in A cannot access B.

## Booking tests
Outside hours, breaks, holidays, staff days off, duration, buffer, overlapping bookings, reschedule, lifecycle transitions and concurrent attempts.

## Usage tests
Same customer with many bookings counts once; normalized identity prevents obvious duplicates; limit edge cases calculate correctly; historical pricing snapshot remains intact.

## Subscription tests
Trial, active, expiry, suspension, cancellation, renewal, upgrade, downgrade and over-limit policy.

## Payment tests
Success/failure/pending, invalid signature, duplicate webhook idempotency, incorrect reference/amount handling and state updates only after verified success.

## RBAC tests
Positive and negative cases for every role.

## Security QA
Authorization bypass, CSRF, validation, rate limits, upload safety and sensitive-data non-disclosure.

## Release gate
Full tests, static analysis, formatting, production build, tenant isolation review, payment sandbox checks, mobile booking, Arabic/English, RTL/LTR, dark/light, SEO/sitemap/robots.
