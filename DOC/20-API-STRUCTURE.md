# Velto — Backend API Structure

This document defines the future API boundary required by the product specification. The current web application is Blade-first; these endpoints are the planned API contract for mobile apps and third-party integrations and are not required by the current browser UI.

## Base

- Prefix: `/api/v1`
- Authentication: Sanctum for authenticated API users.
- Every tenant-scoped endpoint resolves the current tenant from the authenticated membership/context.
- Clients never submit a trusted `tenant_id` to select a workspace.
- Authorization is enforced server-side using the same RBAC policies as the web application.

## Authentication

- `POST /auth/login`
- `POST /auth/logout`
- `GET /auth/user`

## Workspace

- `GET /workspace`
- `GET /workspace/modules`
- `PUT /workspace/modules`

## Services

- `GET /services`
- `POST /services`
- `GET /services/{service}`
- `PUT /services/{service}`
- `DELETE /services/{service}`

## Staff

- `GET /staff`
- `POST /staff`
- `GET /staff/{staff}`
- `PUT /staff/{staff}`
- `PATCH /staff/{staff}/status`
- `PUT /staff/{staff}/services`
- `PUT /staff/{staff}/working-hours`
- `PUT /staff/{staff}/days-off`
- `PUT /staff/{staff}/availability`

## Scheduling / Availability

- `GET /availability`
- `GET /calendar`
- `GET /scheduling/business-hours`
- `PUT /scheduling/business-hours`
- `PUT /scheduling/breaks`
- `PUT /scheduling/holidays`
- `PUT /scheduling/special-hours`

## Bookings

- `GET /bookings`
- `POST /bookings`
- `GET /bookings/{booking}`
- `PATCH /bookings/{booking}/status`
- `POST /bookings/{booking}/reschedule`

Booking writes remain subject to the same availability and concurrency rules used by the web application.

## Customers

- `GET /customers`
- `POST /customers`
- `GET /customers/{customer}`
- `PUT /customers/{customer}`

## Payments

Platform subscription payments and customer booking payments use the same provider-neutral payment core but remain separate payable domains.

- `POST /bookings/{booking}/payment`
- `GET /payments`
- `GET /payments/{payment}`
- `POST /subscriptions/{subscription}/payment`

Provider callbacks/webhooks remain server-to-server endpoints and never trust browser redirects as payment confirmation.

## Subscription / Usage

- `GET /subscription`
- `POST /subscription/renew`
- `POST /subscription/change-plan`
- `POST /subscription/cancel`
- `POST /subscription/reactivate`
- `GET /subscription/usage`
- `GET /subscription/usage-history`

## Notifications

- `GET /notifications`
- `POST /notifications/{notification}/read`

## Business Profile / Settings

- `GET /business`
- `PUT /business`
- `GET /settings`
- `PUT /settings`

## Platform Admin API

Platform administration is isolated from tenant APIs and requires platform-admin authorization.

- `GET /admin/businesses`
- `PATCH /admin/businesses/{tenant}/status`
- `GET /admin/users`
- `GET /admin/plans`
- `POST /admin/plans`
- `PUT /admin/plans/{plan}`
- `GET /admin/subscriptions`
- `GET /admin/payments`
- `GET /admin/usage`
- `GET /admin/reports`
- `GET /admin/support`
- `PUT /admin/settings`

## Response conventions

- JSON only.
- Validation failures use HTTP 422.
- Authentication failures use HTTP 401.
- Authorization failures use HTTP 403.
- Missing resources use HTTP 404.
- Conflict conditions such as booking collisions or duplicate webhook processing use HTTP 409 where appropriate.
- Monetary values are represented as integer minor units plus an explicit ISO currency code.
- Date/time responses are ISO 8601 UTC; user-facing calendar conversion happens in the tenant timezone.

## Security rules

- Never accept `tenant_id` as an authority for tenant selection.
- Apply tenant ownership and authorization checks to every resource.
- Use idempotency keys for payment-creation operations.
- Verify provider signatures server-side.
- Do not expose secrets or gateway credentials through API responses.
