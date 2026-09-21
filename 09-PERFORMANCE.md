# BookResa — Performance Engineering

## Goal
The public booking experience and business dashboards must feel extremely fast.

## Core strategy
Blade SSR + minimal JS + Alpine.js + optimized MySQL + composite indexes + eager loading + pagination + smart caching + queues + Redis + OPcache.

## Database rules
Avoid N+1, unnecessary SELECT *, unbounded all(), PHP-side filtering of large datasets and unindexed tenant/date queries.

Prefer targeted selects, eager loading, pagination and measured query plans.

## Availability
Scope reads by tenant, date/range, relevant staff and service. Never scan the entire tenant booking history.

## Caching
Good candidates:
- modules
- plans
- business types
- tenant settings
- public business profile

Live availability is never considered authoritative from cache.

## Queues
Email, notifications, reminders, exports, heavy reports, image processing and non-critical webhook processing are asynchronous.

## Redis
Production use:
cache, queue backend, rate limiting, atomic/distributed locks, temporary data.

Local Herd can start without Redis while keeping adapters/config Redis-ready.

## Frontend
No SPA boot cost. Use server-rendered HTML and small Alpine components. Async only where it improves UX.

## PHP production
OPcache, optimized Composer autoload, production config/cache, queue workers and scheduler. Octane/FrankenPHP is optional after real measurement.

## Measurement
Track request duration, query count/time, memory, cache hit rate and frontend metrics.
