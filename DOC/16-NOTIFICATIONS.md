# BookResa — Notifications

## Scope

The notification backend is provider-neutral and uses Laravel's native notification system.

## Channels

- Database notifications for in-app consumption.
- Mail notifications when the recipient has an email address.
- Queued delivery through Laravel's queue worker.
- Notifications are configured with after-commit delivery so rolled-back transactions do not emit lifecycle messages.

## Booking notifications

- Booking received: sent when a booking is created.
- Booking confirmed: sent on the pending → confirmed transition.
- Booking cancelled: sent on cancellation.
- Booking rescheduled: sent on reschedule.
- Booking reminder: sent for confirmed bookings approaching the configured reminder window.

All booking notification payloads contain English and Arabic title/message values.

## Payment notifications

For payments attached to a booking:

- payment paid
- payment failed

Payment notifications are emitted only when the local payment status actually changes to the target state.

## Billing notifications

For the active primary workspace member:

- subscription expiry warning
- customer usage warning

The billing command deduplicates notifications by recipient/type over the recent notification window.

## Scheduler

The application schedules:

- booking reminders hourly
- billing notifications daily at 09:00 application time

Run a worker in production:

```bash
php artisan queue:work
```

Run the scheduler continuously in production:

```bash
php artisan schedule:work
```

For manual verification:

```bash
php artisan bookresa:send-booking-reminders --hours=24 --window=60
php artisan bookresa:send-billing-notifications
```

## Tenant safety

Booking/customer notifications are generated from tenant-owned booking/payment records. Billing notifications resolve the tenant primary membership before notifying the workspace owner. Commands explicitly establish tenant context while reading tenant-scoped models.

## Testing

NotificationDeliveryTest covers:

- booking receipt payload
- lifecycle notifications
- payment paid/failed notifications
- reminder idempotency
- subscription expiry warning
- customer usage warning
