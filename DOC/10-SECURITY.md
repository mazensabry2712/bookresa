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

## Commission and settlement controls

The current platform commission/payout actions require the finance permission. The payout ledger only reserves a recorded balance and records an external transfer reference; the application does not itself execute a bank or marketplace transfer. Marking a payout paid is a record of an externally completed transfer, not a transfer API call.

The planned monthly booking-commission model must enforce server-side tenant scoping, finance authorization, immutable/auditable policy snapshots, idempotent cash collection and refund adjustments, and audit logs for receivable, settlement and payout transitions. Never trust a client-supplied tenant ID or treat a cash booking as a verified online payment. See DOC/21 for the rollout gates; do not activate the new commission collection model before the ledger and funds flow are approved and tested.

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
