# BookResa — System Architecture

## Architecture style
Use a Modular Monolith. Keep clear module boundaries without premature microservices.

## Runtime stack
- Laravel 13
- PHP 8.4
- MySQL 8.x
- Blade
- Alpine.js
- Tailwind CSS
- Vite
- Redis-ready cache/queue/lock layer
- OPcache in production

## Request flow
Browser → Blade/Alpine → HTTP layer → Application services/use-cases → Domain modules → Infrastructure adapters → MySQL/Redis/storage/external providers.

## Domain modules
Identity, Tenant, Business, Module, Service, Staff, Customer, Availability, Booking, Subscription, Billing, Payment, Notification and SEO.

## Core services
- TenantResolver: resolves the current tenant from authenticated membership/context.
- BookingService: coordinates booking creation and lifecycle.
- AvailabilityService: calculates available slots.
- CustomerUsageService: counts unique customers and calculates usage.
- SubscriptionService: manages subscription lifecycle.
- PaymentService: provider-neutral payment orchestration.

## Tenancy
MVP uses one MySQL database with tenant_id on tenant-owned records. Isolation is enforced in middleware/context, queries/scopes, policies, services, constraints and tests. Frontend tenant_id is never authorization proof.

## Frontend
Blade is the primary renderer. Alpine.js handles local interactions only. Do not introduce React, Vue, Livewire or Inertia for the MVP.

## Performance
Targeted queries, proper composite indexes, eager loading, pagination, smart caching, asynchronous queues, Redis in production and OPcache.

## Packages
Focused runtime packages:
- laravel/fortify
- spatie/laravel-permission
- spatie/laravel-activitylog
- spatie/laravel-sitemap
- intervention/image

Development:
- pestphp/pest
- pestphp/pest-plugin-laravel
- larastan/larastan
- laravel/pint
- barryvdh/laravel-debugbar (dev only)

Avoid packages for functionality already covered by Laravel.

## API readiness
MVP is Blade-first. Add versioned APIs and Sanctum when mobile/third-party API work actually starts.

## Timezone policy

- Application infrastructure defaults to UTC.
- Each tenant's BusinessProfile owns the business timezone.
- Business hours, breaks, holidays, staff availability and public booking times are interpreted in the tenant timezone.
- Booking timestamps are persisted as UTC instants.
- Public availability accepts a calendar date and returns local times for the tenant timezone.
- Payment expiry and payment timestamps are handled in UTC; UI presentation may convert to the tenant timezone.
- A tenant timezone must be a valid IANA timezone.
