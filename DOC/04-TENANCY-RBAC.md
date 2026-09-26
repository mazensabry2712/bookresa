# BookResa — Tenancy and RBAC

## Tenant
One Business = one Tenant/Workspace in the MVP.

Tenant-owned data:
- business profile
- services
- staff
- customers
- schedules
- bookings
- settings
- tenant payments
- usage records

## Tenant resolution
Authenticated User → Tenant Membership → Current Tenant Context.

A client-supplied tenant_id is never trusted as proof of access.

## Isolation
Every tenant-owned read/write path must enforce tenant context through middleware/services/scopes/policies and suitable database constraints.

Avoid unscoped resource lookups such as Booking::find($id) for tenant resources.

## Roles
Platform:
- Platform Admin

Business:
- Owner
- Manager
- Receptionist
- Staff Member

## Example permissions
business.view
business.update
services.view/create/update/delete
staff.view/manage
customers.view/create/update
bookings.view/create/update/cancel/complete
schedule management uses calendar.view for access and settings.manage for mutations
calendar.view
billing.view
subscription.manage
settings.manage
reports.view

## Module-aware authorization
Module access is the combination of:
1. global module activation;
2. tenant module state;
3. subscription entitlement for optional/plan-gated modules;
4. the onboarding exception for a newly created tenant that has not yet completed onboarding;
5. the current user's role/permission.

Core modules are available during unfinished onboarding. After onboarding is marked completed, a tenant without a usable subscription cannot continue normal core operations.

Optional modules require an enabled tenant module and a usable subscription whose plan includes the module.

A module is never considered authorized merely because its UI is visible. Backend middleware/services/policies remain authoritative.

## Authorization order
Authenticated → membership → tenant status → module enabled → role/permission → resource policy.

## Platform Admin
Platform authorization is separate from business ownership. An Owner in one tenant does not become a global admin.

## Suspension
Restriction rules are configurable. Never delete tenant data because of suspension.

## Mandatory tests
Cross-tenant read/update/delete attempts, forged IDs, forged tenant_id, direct URL access and role bypass.
