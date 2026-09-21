# BookResa — Subscription and Usage Billing

## Revenue
Primary: subscriptions.
Additional: unique-customer usage fees.
Future: transaction fees and premium modules.

## Plan
- price
- currency
- billing period
- included customer limit
- additional customer unit price
- active state
- enabled modules

## Subscription
- tenant
- plan
- start/end
- status
- payment status
- payment metadata
- pricing snapshot

A trial subscription does not imply a successful payment. Its payment status remains pending until a real subscription-payment flow is completed.

Statuses: Trial, Active, Expired, Suspended, Cancelled.

## Customer usage
Count unique customer profiles inside the tenant, not bookings.

additional_customers = max(current_unique_customers - included_limit, 0)
usage_charge = additional_customers × additional_customer_price
total = base_price + usage_charge

## Pricing snapshots
Historical billing stores the exact inputs used for the charge. Future plan edits cannot change historical billing.

## Upgrade
Behavior must be explicit and testable. MVP may use a fixed-period policy; advanced proration can be added later.

## Downgrade
Recommended default: effective at the next billing boundary. Existing customers are never deleted.

Over-limit policy can allow usage charges or restrict creation of new customer profiles.

## Owner visibility
Display current customers, included limit, additional count, usage fee and total subscription cost.

## Admin controls
Plan price, included limit, additional customer price, billing period and enabled modules.

## Correctness
Billing must be deterministic, auditable and idempotent against duplicate jobs/events.
