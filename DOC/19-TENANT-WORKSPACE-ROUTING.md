# BookResa Tenant Workspace Routing

## Decision

BookResa uses a single database with explicit tenant-aware workspace URLs.

The canonical authenticated workspace URL format is:

```
/workspace/{tenant-slug}/dashboard
/workspace/{tenant-slug}/dashboard/bookings
/workspace/{tenant-slug}/dashboard/calendar
```

The tenant slug is the business workspace identifier created during onboarding.

## Multitenancy package

BookResa uses `spatie/laravel-multitenancy` 4.2.1.

The package owns the current-tenant lifecycle and container binding. BookResa keeps its existing tenant-owned model scopes and RBAC team isolation because those are already part of the application data model.

The project intentionally uses the package in single-database mode. No tenant database switching task is configured.

## Request flow

1. The authenticated user opens a workspace URL containing the tenant slug.
2. `ResolveTenant` resolves the tenant from the route.
3. The middleware verifies that the authenticated user has an active membership in that tenant.
4. The tenant is made current through Spatie's tenant lifecycle.
5. Spatie Permission receives the tenant id as its team id.
6. URL defaults add the current tenant slug to named workspace routes generated during the request.
7. Tenant-owned Eloquent models continue to use BookResa's `BelongsToTenant` scope.

## Legacy URL

`/dashboard` remains as a compatibility entry point.

It resolves the user's primary active workspace and redirects to the canonical tenant URL. It is not the canonical workspace URL.

## Security rule

A user who is authenticated in workspace A cannot access workspace B by changing the tenant slug unless they have an active membership in workspace B.

A failed membership check returns HTTP 403.

## Multiple workspaces

A single user can be an active member of more than one tenant. The tenant in the URL determines which workspace is active for that request.

This avoids treating "one user = one tenant" as the tenancy model.

## Public booking

Public booking remains tenant-slug based and does not require authentication:

```
/{tenant-slug}/book
/book/{tenant-slug}
```

Those routes resolve the public booking tenant independently of authenticated workspace membership.
