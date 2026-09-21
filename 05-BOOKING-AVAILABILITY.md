# BookResa — Booking and Availability

## Core
Booking and Availability are platform core services independent of business type.

## Availability inputs
Business hours, breaks, holidays, special hours, staff schedules, staff days off, explicit staff availability, service duration, service buffer, existing bookings, booking rules and tenant timezone.

## Slot calculation
1. Resolve tenant and requested date.
2. Apply business schedule and date overrides.
3. Resolve eligible staff.
4. Apply staff schedule/exceptions.
5. Query only conflicting bookings for relevant date/staff.
6. Generate candidate slots in memory.
7. Remove conflicts and slots that cannot fit duration + buffer.
8. Return bookable slots.

## Time
Persist consistently (prefer UTC). Convert using business/tenant timezone for user-facing schedules and slot generation.

## Booking create
Validate → tenant → service/staff → transaction → re-check availability → lock/serialize → create booking → commit → queue non-critical side effects.

## Double booking
Frontend availability is advisory. Database transaction/locking is authoritative. Concurrent requests must not create overlapping reservations for the same resource.

## Lifecycle
Pending → Confirmed → Completed.
Other controlled transitions include Cancelled, Rescheduled and No-show.

## Reschedule
Secure new slot before releasing old slot.

## Public page
Mobile-first, Blade SSR, minimal JS, minimal queries, optimized images and cached stable business data.

## Tests
Hours, breaks, holidays, days off, duration, buffer, existing conflict, reschedule conflict, concurrency and tenant isolation.
