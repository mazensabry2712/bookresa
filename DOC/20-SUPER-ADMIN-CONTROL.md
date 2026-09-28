# BookResa — SaaS Super Admin Control

## Purpose

BookResa has two separate administration layers:

- **Workspace Owner / Manager** — manages one business workspace and its day-to-day operations.
- **SaaS Super Admin** — platform-level authority owned by the BookResa SaaS operator.

The active PlatformAdmin account is the SaaS Super Admin authority in the current architecture.

## Super Admin scope

A Super Admin can operate across the entire platform:

### Workspace control
- view and search all workspaces
- open a dedicated Workspace Overview with business, owner, members, subscription, usage and recent activity
- inspect business type, status and workspace activity
- activate or suspend a workspace
- open any workspace directly
- manage workspace modules
- suspend/activate workspace members directly from the workspace overview
- suspend/activate an eligible workspace subscription directly from the workspace overview
- enter suspended workspaces for operational control

### Workspace operations
When operating inside a workspace, Super Admin can use the existing workspace UI and routes to manage:

- Dashboard
- Calendar
- Bookings
- Scheduling and availability
- Services
- Staff
- Customers
- Payments
- Billing/subscription
- Reports
- Notifications
- Business profile/settings
- Workspace modules

The platform administrator does not need a separate duplicate version of each tenant screen.

## Access model

### Tenant isolation remains the default

Normal users must have an active membership in the requested workspace.

### Super Admin exception

An active PlatformAdmin may explicitly open any workspace by slug, including a suspended workspace.

The request still resolves a real Tenant and sets the normal CurrentTenant context. This keeps tenant-scoped queries and existing application services working as designed.

### Permission bypass

The Super Admin is allowed through tenant permission checks using Laravel's authorization Gate::before.

This is deliberately restricted to active PlatformAdmin records.

### Module/subscription bypass

Platform-level operation must not be blocked by a workspace's enabled-module or subscription state.

This allows the SaaS owner to inspect and repair a workspace even when:

- an optional module is disabled
- a workspace is suspended
- a subscription is expired or otherwise unusable

## UX

The Platform Admin console lives under /admin.

The workspace list provides:

- **Workspace Overview** — inspect and operate the workspace from one platform-level control page
- **Open workspace** — enter the selected tenant's normal workspace UI
- **Modules** — manage enabled modules
- **Suspend / Activate** — control workspace availability

When a Super Admin is inside a workspace, the workspace shell exposes a **Super Admin** return action back to /admin.

## Security rules

1. Only an authenticated, verified, active PlatformAdmin can access platform administration.
2. Normal users cannot use platform routes.
3. Normal users cannot enter another workspace through the Super Admin exception.
4. Super Admin access does not remove the CurrentTenant context; it selects the requested tenant explicitly.
5. Platform actions continue to use the application's existing audit logging where destructive or sensitive state changes occur.
6. Credentials and payment secrets never belong in source control.

## Performance rules

The Super Admin permission check must not create repeated database queries for every authorization call on a page.

The current implementation reuses the loaded platformAdmin relation when available.

The design intentionally reuses existing workspace controllers and views instead of building a second administrative copy of every operation screen.

## Operational recommendation

Use the platform console as the control center:

/admin → choose workspace → Open workspace → perform the required operation → Super Admin → return to platform console.

For normal business operation, workspace owners and staff continue using their own workspace permissions.

## Verification

The automated test suite covers:

- Super Admin access to a workspace without membership
- Super Admin access to suspended workspaces
- Super Admin access when tenant modules are disabled
- regular user isolation from other workspaces
- existing tenant permission/module restrictions for non-platform users

Production deployment must still verify the actual infrastructure release gates documented in DOC/17-PRODUCTION-READINESS.md.