# BookResa — Security Plan

## Principles
Secure authentication, password hashing, HTTPS, RBAC, strict tenant isolation, server-side authorization, validation, CSRF protection, rate limiting, secure sessions, audit logging, backups, error logging and payment security.

## Tenant security
Highest priority. Every tenant-owned read/write path validates tenant context and permissions.

Test forged resource IDs, forged tenant_id, direct URLs and manipulated requests.

## Authentication
Use Laravel/Fortify and enable only needed authentication features.

## Authorization
Combine Spatie Permission with Laravel Policies/Gates and tenant membership.

## Validation
Use Form Requests/server validation. Client validation is never the security boundary.

## Rate limiting
Protect login, registration, password reset, public booking creation, payment creation and relevant public/support endpoints.

## Payments
Never store raw card data. Verify provider webhook signatures and enforce idempotency.

## Files
Validate MIME/content, size, extension and dimensions where relevant. Store safely and process heavy transformations asynchronously.

## Audit logs
Record booking status changes, subscription changes, pricing changes, permission changes, suspension/reactivation and payment administration.

Never log passwords, card data, webhook secrets or access tokens.

## Backups
Back up MySQL and important files with retention, offsite copies and periodic restore tests.

## Secrets
Use environment variables/secret management. Never commit credentials.

## Headers
Production should use HTTPS, HSTS where safe, content-type protection, frame protection, referrer policy and an appropriate CSP.

## Dependency hygiene
Keep dependencies minimal and updated.
