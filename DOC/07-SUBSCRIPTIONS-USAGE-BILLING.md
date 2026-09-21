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

Platform pricing is managed in a platform-only admin area. Plan edits affect future subscriptions; existing subscriptions retain their pricing snapshots.

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
Display current customers, included limit, additional count, usage fee and total subscription cost. The owner billing dashboard also exposes usage history, scheduled plan changes and cancellation/re-activation state.

## Admin controls
Plan price, included limit, additional customer price, billing period and enabled modules.

## Correctness
Billing must be deterministic, auditable and idempotent against duplicate jobs/events.


## Lifecycle policy

- Cancellation is scheduled at the current period boundary; access remains available until `end_at`.
- A scheduled cancellation can be reactivated before the boundary.
- Plan changes are scheduled for the next billing boundary through `next_plan_id` and `plan_change_effective_at`; customers are never deleted because of plan limits.
- Expiry is deterministic: Trial/Active subscriptions with `end_at <= now` become Expired.
- The expiry command is scheduled hourly and uses overlap protection.
- Renewal is explicit in the MVP: an Expired subscription can be renewed using its scheduled next plan (or current plan) and is reset to payment pending before a new subscription payment settles it.
- A paid Active subscription is usable only after its subscription payment is Paid. A Trial subscription remains usable during the trial window.


## Subscription payment

Paid plans use the shared provider-neutral payment core with a `Subscription` payable. The Business subscription payment is separate from customer booking payments.

The configured payment provider is resolved by the application container. The domain checkout service depends on the `PaymentGateway` contract, while the infrastructure binding selects the active provider (Kashier for the current MVP).

For Kashier, the hosted Payment Session is created through `POST /v3/payment/sessions`; the returned `sessionUrl` is the checkout destination. The server verifies the payment session and also accepts signed server-to-server webhook notifications. The subscription payment is idempotent per tenant/provider/subscription.

Lifecycle entitlement rules remain:
- Trial: usable during the trial period without requiring a paid subscription payment.
- Active + Paid: usable.
- Active + Pending/Failed/Cancelled/Refunded payment: not usable.
- Expired/Suspended/Cancelled subscription: not usable.
