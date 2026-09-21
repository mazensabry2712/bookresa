# BookResa — Security Plan

## Principles
Authentication, password hashing, HTTPS, RBAC, strict tenant isolation, server authorization, validation, CSRF protection, rate limiting, secure sessions, audit logs, backups, error logging and payment security.

## Tenant security
Highest priority. Every tenant-owned read/write path validates tenant context and permissions.

Test forged IDs, forged tenant_id, manipulated forms and direct URLs.

## Authentication
Use Laravel/Fortify and enable only needed authentication features.

## Authorization
Combine Spatie Permission with Laravel Policies/Gates and tenant membership.

## Validation
Use Form Requests/server-side validation. Client validation is supplementary.

## Rate limits
Protect login, registration, password reset, public booking creation, payment creation and appropriate public/support endpoints.

## Payments
Never store raw card data. Verify payment webhooks and enforce idempotency.

## Files
Validate type, size, extension and dimensions where relevant. Store safely. Process heavy images asynchronously.

## Audit log
Record booking status changes, subscription changes, pricing changes, permission changes, suspension/reactivation and payment administration.

Never log passwords or secrets.

## Backups
Back up database and important files. Define retention, offsite copy and restore testing.

## Secrets
Environment/secret manager only.

## Headers
Production should use HTTPS, HSTS where safe, content type protection, frame protection, referrer policy and appropriate CSP.

## Dependencies
Keep dependency count low and patch regularly.
