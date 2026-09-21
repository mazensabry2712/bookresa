# BookResa — Tenancy and RBAC

## Tenant model
One Business = one Tenant/Workspace for the MVP.

Tenant-owned data includes:
profile, services, staff, customers, schedules, bookings, settings, tenant payments and usage.

## Tenant resolution
Authenticated User → Tenant Membership → Current Tenant Context.

Frontend tenant_id is never trusted as authorization.

## Isolation
All tenant reads/writes must enforce tenant context using middleware/services/scopes/policies and appropriate DB constraints.

Avoid direct unscoped access such as Booking::find($id) for tenant resources.

## Roles
Platform:
- Platform Admin

Business:
- Owner
- Manager
- Receptionist
- Staff Member

## Permissions
Examples:
business.view/update
services.view/create/update/delete
staff.view/manage
customers.view/create/update
bookings.view/create/update/cancel/complete
calendar.view
billing.view
subscription.manage
settings.manage
reports.view

## Authorization chain
Authenticated → membership → tenant status → module enabled → permission → resource policy.

## Platform Admin
Platform access is distinct from business ownership. Do not grant global access from tenant ownership.

## Suspension
Restrict operational access according to business policy while retaining support/billing paths. Never delete data on suspension.

## Required tests
Cross-tenant read/update/delete, forged IDs, forged tenant_id, direct URL access and role bypass.
