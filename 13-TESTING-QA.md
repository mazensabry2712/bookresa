# BookResa — Testing and QA

## Tooling
Pest, Larastan and Pint. Debugbar is development-only.

## Unit tests
Slot calculations, usage math, price calculations and status-transition rules.

## Feature tests
Authentication, tenant access, CRUD, public booking, billing, webhooks and admin permissions.

## Mandatory tenant tests
Tenant A cannot read/update/delete Tenant B resources. Forged tenant_id cannot switch context. Staff from A cannot access B.

## Booking tests
Outside hours, breaks, holidays, days off, duration, buffer, overlaps, reschedule, lifecycle transitions and concurrent attempts.

## Usage tests
Same customer with many bookings counts once; normalization prevents obvious duplicates; limit edge cases and charges are correct; snapshots persist.

## Subscription tests
Trial, active, expiry, suspension, cancellation, renewal, upgrade, downgrade and over-limit policy.

## Payment tests
Success/failure/pending, invalid signature, duplicate webhook idempotency, mismatched reference/amount and state updates only after verified success.

## RBAC tests
Positive and negative cases for every role.

## Security QA
Authorization bypass, CSRF, validation, rate limiting, upload safety and secret non-disclosure.

## Release gate
All tests green, static analysis, formatting, production build, tenant isolation review, payment sandbox tests, Arabic/English, RTL/LTR, dark/light, mobile booking, SEO/sitemap/robots.
