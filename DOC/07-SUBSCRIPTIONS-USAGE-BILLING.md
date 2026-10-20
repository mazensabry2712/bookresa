# BookResa — Subscription and Usage Billing

## Revenue and implementation status

- **Current implemented billing path:** subscription payments and subscription base pricing.
- **Unique-customer usage:** the app measures unique customer count and over-limit count. At the audited baseline, `CalculateSubscriptionUsage` sets `additionalCustomerPriceMinor = 0` and `usageChargeMinor = 0`, and the current plan-admin save path stores `additional_customer_price_minor = 0`. The current Plan Admin UI explicitly says additional-customer surcharges are replaced by booking commissions. Older specification text describing a per-customer surcharge is stale relative to this current product direction; do not reactivate that surcharge unless explicitly re-approved.
- **Booking commissions:** a separate target policy provides a default 1,000 qualifying bookings per workspace/calendar month and 10% on eligible bookings after that allowance. This target is not implemented at the audit baseline; current eligibility uses customer-profile ordinal and a 5% fallback. See DOC/21.
- Optional premium modules and any future fee models require explicit product approval and verified accounting/collection paths.

Do not combine unique-customer usage counts with booking counts, and do not present a planned surcharge or commission as collected revenue.

## Plan
- price
- currency
- billing period
- included customer limit (for unique-customer metering and customer-creation policy)
- active state
- enabled modules

The legacy `additional_customer_price_minor` column remains in stored plan/subscription data, but it is currently written as zero and is not an active pricing control.

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

`additional_customers = max(current_unique_customers - included_limit, 0)`

This is a usage meter. At the current baseline, the extra-customer price and usage charge are zero; do not apply `additional_customers × additional_customer_price` as a payable charge. The separate monthly booking commission is based on qualifying booking count and is not implemented yet.

## Pricing snapshots
Historical billing stores the exact inputs used for the charge. Future plan edits cannot change historical billing.

## Upgrade
Behavior must be explicit and testable. MVP may use a fixed-period policy; advanced proration can be added later.

## Downgrade
Recommended default: effective at the next billing boundary. Existing customers are never deleted.

Over-limit policy can allow usage charges or restrict creation of new customer profiles.

Current BookResa defaults:
- customer limit policy: `allow_overage`
- usage warning threshold: 75% of the included customer limit

The strict `block_new_customers` policy is supported for tenants that want creation blocked at the limit.

## Owner visibility
Display current customers, included limit, additional count and subscription base-price details. The owner billing dashboard also exposes usage history, scheduled plan changes and cancellation/re-activation state. The extra-customer usage charge is currently zero and must not be presented as a payable surcharge.

## Admin controls
Plan price, included limit, billing period and enabled modules. The current Plan Admin does not expose an additional-customer price field and saves the legacy field as zero.

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


## Relationship to booking commissions

Subscription usage is measured in unique customer profiles; the monthly commission allowance must be measured in qualifying bookings. These are separate dimensions and must not reuse each other's limits or counters. The legacy commission implementation currently violates that target separation by consulting the subscription's `included_customer_limit`; DOC/21 records the gap and required tests. The current product direction treats the extra-customer surcharge as replaced by booking commissions, so the commission implementation must not silently introduce a second customer-based charge.

## Subscription payment

Paid plans use the shared provider-neutral payment core with a `Subscription` payable. The Business subscription payment is separate from customer booking payments.

The configured payment provider is resolved by the application container. The domain checkout service depends on the `PaymentGateway` contract, while the infrastructure binding selects the active provider (Kashier for the current MVP).

For Kashier, the hosted Payment Session is created through `POST /v3/payment/sessions`; the returned `sessionUrl` is the checkout destination. The server verifies the payment session and also accepts signed server-to-server webhook notifications. The subscription payment is idempotent per tenant/provider/subscription.

Lifecycle entitlement rules remain:
- Trial: usable during the trial period without requiring a paid subscription payment.
- Active + Paid: usable.
- Active + Pending/Failed/Cancelled/Refunded payment: not usable.
- Expired/Suspended/Cancelled subscription: not usable.
