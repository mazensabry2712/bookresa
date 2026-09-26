# BookResa — Booking and Availability

## Core principle
Booking and Availability are platform core services and must not contain business-type-specific code.

## Availability inputs
- business working hours
- breaks
- holidays
- special hours
- staff working hours
- staff days off
- staff explicit availability
- service duration
- service buffer
- existing bookings
- booking rules
- tenant timezone

## Slot calculation
1. Resolve tenant and requested date.
2. Apply business schedule and date overrides.
3. Resolve eligible staff.
4. Apply staff schedule/exceptions.
5. Query only conflicting bookings for relevant tenant/date/staff.
6. Generate candidate slots in memory.
7. Remove conflicts and slots that cannot fit duration + buffer.
8. Return bookable slots.

## Time
Persist consistently (prefer UTC). Convert using the business/tenant timezone for schedules and UI.

## Booking creation
Validate → resolve tenant/service/staff → DB transaction → re-check availability → lock/serialize → create booking → commit → queue non-critical side effects.

## Double booking
Frontend availability is advisory. Database state is authoritative. Concurrent requests must not create overlapping bookings for the same resource.

## Lifecycle
Normal: Pending → Confirmed → Completed.
Controlled alternatives: Cancelled, Rescheduled and No-show.

## Rescheduling
Rescheduling must be atomic from the user's perspective: the new slot must be validated and protected against concurrent conflicts before the final rescheduled booking is committed.

An implementation may temporarily neutralize the existing booking inside the same database transaction so availability can evaluate the new slot, but failure must roll back the original booking state. A successful operation commits the new time/staff and marks the booking Rescheduled.

## Public booking
Mobile-first, Blade SSR, minimal JS, minimal DB queries, optimized images and cached stable business data.

## Tests
Working hours, breaks, holidays, staff days off, duration, buffer, existing conflicts, reschedule conflicts, concurrent attempts and tenant isolation.
