# BookResa — Performance Engineering

## Goal
Public booking pages and business dashboards should feel extremely fast. Performance is an architectural requirement.

## Core rules
- Blade SSR
- minimal JavaScript
- Alpine for local interaction
- optimized MySQL
- composite indexes
- eager loading
- pagination
- smart caching
- queues
- Redis in production
- OPcache

## Database
Avoid N+1 queries, unneeded SELECT *, unbounded all(), PHP-side filtering of large tables and unindexed tenant/date lookups.

Prefer targeted selects, eager loading, pagination and measured query plans.

## Availability
Scope queries by tenant, date/range, relevant staff and service. Never scan an entire tenant's bookings for a single slot request.

## Caching
Good candidates:
- modules
- plans
- business types
- tenant settings
- public business profile

Live availability is never authoritative from stale cache.

## Queues
Email, notifications, reminders, exports, heavy reports, image processing and non-critical webhook work are asynchronous.

## Redis
Production uses Redis for cache, queue backend, rate limiting, atomic/distributed locks and temporary data. Local Herd can remain simple while code stays Redis-ready.

## Frontend
Avoid SPA boot overhead. Use server-rendered HTML with small Alpine components and selective async updates.

## PHP production
Enable OPcache, optimized Composer autoload, production config/cache, supervised workers and scheduler. Octane/FrankenPHP is optional after measurement.

## Measurement
Track request time, DB query count/time, memory, cache hit rate and frontend metrics.

## Implemented hardening
- Availability calculation batches staff day-offs, explicit availability, recurring working hours and bookings to avoid per-staff N+1 queries.
- Booking management and calendar use pagination/range-bounded queries where result sets can grow.
- Service management now paginates at 20 records per page.
- Active business types used by onboarding use a short cache with explicit invalidation after the seeder runs.
- Pricing and security-critical module checks intentionally remain database-backed so authorization and current pricing/module state are not served from stale cache.
- Composer PHP target is aligned to PHP 8.4.
