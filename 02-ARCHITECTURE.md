# BookResa — System Architecture

## Architecture style
Use a Modular Monolith. Keep clear module boundaries without premature microservices.

## Runtime
- Laravel 13
- PHP 8.4
- MySQL 8.x
- Blade
- Alpine.js
- Tailwind CSS
- Vite
- Redis-ready cache/queue/lock
- OPcache in production

## Layers
Browser → Blade/Alpine → HTTP → Application services/use-cases → Domain modules → Infrastructure adapters → MySQL/Redis/storage/providers.

## Domain modules
Identity, Tenant, Business, Module, Service, Staff, Customer, Availability, Booking, Subscription, Billing, Payment, Notification, SEO.

## Core application services
- TenantResolver
- BookingService
- AvailabilityService
- CustomerUsageService
- SubscriptionService
- PaymentService

## Frontend
Blade is the primary renderer. Alpine.js handles local interactions. No React/Vue/Livewire/Inertia for MVP.

## Multi-tenancy
Shared MySQL database with tenant_id on tenant-owned data. Enforce isolation in middleware/context, policies, queries, services, constraints and tests.

## Performance
Use targeted DB queries, composite indexes, eager loading, pagination, smart caching, queues, Redis in production and OPcache.

## Packages
Keep dependencies minimal and focused:
- Fortify for authentication
- Spatie Permission for RBAC
- Spatie Activitylog for audit trail
- Spatie Sitemap for public sitemap
- Intervention Image for image processing
- Pest/Larastan/Pint/Debugbar for development quality

Do not add a package when Laravel-native functionality is sufficient.

## API readiness
MVP stays Blade-first. Add versioned API routes and Sanctum when mobile/third-party API work begins.
