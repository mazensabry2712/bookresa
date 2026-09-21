# BookResa — Subscription and Usage Billing

## Revenue
Primary: subscriptions.
Additional: unique-customer usage.
Future: transaction fees and premium modules.

## Plans
Price, currency, billing period, included customer limit, additional customer price, active state, enabled modules.

## Subscriptions
Tenant, plan, start/end, status, payment metadata and pricing snapshot.

Statuses: Trial, Active, Expired, Suspended, Cancelled.

## Customer usage
Count unique customer profiles inside the tenant, not bookings.

additional = max(current_unique_customers - included_limit, 0)
usage_charge = additional × additional_customer_price
total = base_price + usage_charge

## Pricing snapshots
Billing history preserves the exact price/limits used to calculate each charge. Future plan edits cannot change historical records.

## Upgrade
Behavior must be explicit and testable. Simpler fixed-period upgrade may be used in MVP; advanced proration can be later.

## Downgrade
Recommended default: effective at next billing boundary. Never delete existing customers. Over-limit policy may allow usage fees or restrict creation of additional profiles.

## Admin controls
Plan price, included limit, extra-customer price, billing period and enabled modules.

## Owner visibility
Show current customers, included limit, extra customers, usage fee and total subscription cost.

## Correctness
Billing jobs and webhook processing must be deterministic and idempotent.
