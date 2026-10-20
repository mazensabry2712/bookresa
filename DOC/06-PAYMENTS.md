# BookResa — Payment Architecture

## Goal

BookResa has a provider-neutral Payment Core. Kashier is the current MVP provider. Adding another gateway should not require rewriting Booking, Subscription, or Billing.

## Domain and infrastructure

- Domain: `Payment`, `PaymentService`, `PaymentGateway`, `PaymentRequest`, and `PaymentGatewayResult`.
- Kashier adapter: `app/Infrastructure/Payments/Kashier/`.
- Payment contexts are polymorphic: a Business pays BookResa for a subscription; a Customer pays for a Business booking/service. They share gateway infrastructure but remain separate business concepts.
- Payment records retain the payable context, reference, provider references, amount in minor units, currency, status, method, checkout URL, metadata, timestamps, and refund totals.

Domain services depend on `PaymentGateway`, not Kashier-specific classes.

## Current booking-payment behavior — implementation status

The effective switch is the **platform setting** `centralized_booking_payments_enabled`, managed in Platform Settings. It is not an independent per-workspace merchant-gateway switch.

- The platform setting controls whether online checkout is available, not whether online payment is mandatory for every public booking.
- With the setting off, the booking page only permits **cash at the center**; the online option is displayed as unavailable and the backend rejects a forged `payment_method=online` selection before creating a booking.
- With the setting on, the customer chooses between **cash at the center** and **full online payment**. Cash creates the booking without starting a payment session; online requires customer email and redirects to hosted Kashier checkout for the full service price.
- The default hosted-checkout methods are `card,wallet`, covering Visa/Mastercard-type cards and mobile wallets when enabled for the merchant. Kashier filters checkout to methods enabled on the specific live merchant account; configuring `allowedMethods` does not activate a method at the provider. Fawry is intentionally not requested by default; add it only after Kashier confirms it is enabled and available for this live merchant.
- Kashier test mode forces hosted checkout to card and wallet. Before enabling production payments, confirm live mode/credentials, live webhook delivery and merchant method entitlements; then perform a minimal real card and wallet payment and refund/reconcile each.
- The selected method is stored in `bookings.payment_method` (`cash` or `online`). The column is nullable so legacy and internal bookings are not falsely assigned a method.
- The current `StartBookingPayment` implementation explicitly charges the full price. Deposit percentages are not used to calculate the checkout amount, so **Deposit remains a product requirement but is not an available booking checkout mode**.
- Centralized checkout uses the platform Kashier merchant account. Workspace-connected merchant collection is not the current public booking flow. Proceeds settle to that platform merchant; the BookResa payout ledger records manual payouts and does not send transfers automatically, so confirm the intended funds flow before enabling customer charges.

If checkout-session creation fails before there is evidence that a provider session exists, the controller attempts to cancel the pending booking. Once a real checkout session exists, a customer abandoning checkout can leave the booking pending: payment expiry is stored, but the current scheduler does not include a job that releases abandoned pending booking slots. Pending bookings are included in availability blocking. This remains a release-readiness item.

**Retry gap:** the current booking confirmation page can link to the saved checkout URL, but there is no dedicated customer-facing action that safely starts a fresh payment session for an existing booking after an expired or failed session. Until that flow exists, a customer may be unable to retry cleanly and the pending booking may keep blocking its slot.

## Subscription payment

Paid business subscriptions use the same gateway-neutral payment core with a `Subscription` payable. Trial subscriptions do not require payment. A paid subscription is usable only according to the subscription lifecycle and its paid payment state.

Subscription and customer-booking payment records are separate; a successful customer booking payment must not activate a business subscription, or vice versa.

## Kashier checkout, return, and webhook

1. Create a local pending payment with an idempotency key.
2. Create a Kashier hosted session and store its provider reference, checkout URL, and expiry.
3. Redirect the browser to the hosted checkout.
4. Validate the signed merchant-return parameters and perform server-side session verification. A browser redirect alone is not proof of payment. The server-verified session's merchant ID, merchant order reference, amount and currency must match the local payment before its state is applied. `AUTHORIZED` stays `Processing`; it is not treated as a captured/paid payment.
5. Verify webhook signatures with the Payment API Key, validate the payment reference, merchant, amount, and currency, then update the payment and related booking/subscription. Map status by operation: a generic `SUCCESS` event is not enough to make an authorization, void or rejection a paid payment.
6. Use idempotency and transaction locking so repeated provider notifications do not apply the same payment/refund twice. The event operation is part of the idempotency key: distinct operations such as `pay` and `capture` must not suppress each other merely because transaction ID and status match.

**Webhook acknowledgement contract:** return HTTP `200` for a newly processed delivery and HTTP `409` for an already-processed duplicate. Kashier treats other response codes, including `204`, as unacknowledged and may retry the event. The controller and feature tests now use this contract.

Kashier's per-session `serverWebhook` can receive multiple operation events, not just `pay`. The handler currently interprets `data.amount` as a major-unit amount and converts it to minor units. Kashier's standard payload example shows `amount` as a JSON number (for example `11334`) but does not explicitly label its unit on the payload page; the separate Wix-specific documentation says standard Kashier `data.amount` is the plain order amount, unlike Wix refund notifications. Therefore the real sandbox release gate must confirm this exact field using a known charge and its webhook (and a partial refund if supported), and provider operation/status semantics—especially authorization, capture, void, and reversal—before live collection is enabled.

## Commission ledger and workspace payouts

Booking commission is snapshotted when checkout starts and reconciled after paid/refunded outcomes. Commission and workspace net are stored separately from the raw payment record.

The current platform payout screen is a **manual payout ledger**: creating a payout reserves the recorded available balance, and marking it paid records an external transfer reference. It does not initiate a bank or Kashier transfer itself. Do not describe this as automated merchant settlement.

## Refunds

The gateway contract supports refunds. The current platform-admin flow requests the remaining refundable amount, while webhook reconciliation also accounts for partial-refund events. Refund behavior, amount/currency validation, cumulative partial refunds, and duplicate notifications must remain tested against actual sandbox payloads.

## Security

- Never store raw card PAN/CVV.
- Store the platform Kashier live Merchant ID and credentials through Super Admin Integrations. The Payment API Key and Secret Key are encrypted at rest with Laravel Crypt in `platform_settings`, never re-rendered into the admin page, and never written to the audit log. `APP_KEY` must remain protected and stable because it decrypts these stored values. Once this integration is saved, the database-backed Super Admin configuration is the production source of truth; environment values remain a fallback only before that record exists.
- Verify redirect signatures and webhook HMAC signatures.
- Verify provider state server-side; never mark a payment paid from a browser redirect alone.
- Preserve tenant ownership checks, amount/currency/merchant validation, idempotency, audit entries, and refund limits.

## Adding another provider

Implement the `PaymentGateway` contract, add provider configuration/client and signed-result verification, register the adapter, and add provider contract tests. Keep provider-specific code in Infrastructure.

## Release gates — not proven by mocked tests

The real Kashier sandbox end-to-end check remains incomplete until a real test checkout has been completed and the return, webhook, duplicate delivery, booking/subscription synchronization, and refund paths have been verified against the current Kashier test account. Confirm the actual webhook amount format using a known payment amount and validate all configured event types before enabling live collection.


## Monthly booking commission policy — audit note (2026-10-20)

The requested target policy is a 1,000-booking free allowance per workspace per calendar month and a 10% default commission on eligible bookings beyond the allowance, controlled from Super Admin. This target is documented but is **not implemented at the current audit baseline**.

The current code still bases eligibility on customer-profile ordinal, may inherit the active subscription's included-customer limit, and defaults to 5%. Cash bookings bypass online gateway checkout, while the current workspace payout view is a manual settlement ledger, not an automatic transfer integration. Do not enable live charges on the assumption that the target policy or marketplace split settlement exists.

See [DOC/21-BOOKING-COMMISSION-AUDIT-AND-IMPLEMENTATION-PLAN.md](21-BOOKING-COMMISSION-AUDIT-AND-IMPLEMENTATION-PLAN.md) for the current audit, required code touchpoints, cash/online funds model, ledger design, provider approval, test matrix, migration safety, and implementation phases. Keep this document in sync when that implementation is delivered.
