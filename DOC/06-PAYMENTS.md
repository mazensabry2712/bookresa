# BookResa — Payment Architecture

## Goal
BookResa owns a provider-neutral Payment Core. MVP provider: Kashier. Future providers must be addable without rewriting Booking, Subscription or Billing.

## Domain layer
- Payment model
- PaymentService
- PaymentGateway contract
- payment request/result DTOs

## Infrastructure
Example:
Infrastructure/Payments/Kashier/
- KashierGateway
- KashierClient
- KashierWebhookVerifier
- KashierMapper

Domain code never imports provider-specific classes.

## Contract capabilities
- create payment/session
- verify payment
- refund when supported
- map provider states to BookResa internal states

## Payment contexts
1. Business → BookResa subscription.
2. Customer → Business booking/service.

They share infrastructure but remain separate business concepts.

## Payment record
Provider-neutral references, payable context, amount_minor, currency, status, method, checkout_url, metadata and paid_at.

## Kashier flow
Create payment/session → secure provider checkout → webhook → verify signature → idempotency → persist result → update Booking/Subscription → queue notifications.

A browser redirect alone never marks a payment successful. BookResa validates the Kashier redirect signature, then performs server-side session verification; the webhook remains the authoritative asynchronous reconciliation path.

## Public booking checkout

When a tenant enables `booking_settings.payment_required`, the public booking flow creates the booking first, creates a provider-neutral Payment, sends the customer to Kashier's hosted `sessionUrl`, and returns to BookResa through the signed merchant redirect. The payment record stores the checkout URL so a pending/failed payment can be retried without creating another payment for the same booking.

The default onboarding setting is `payment_required=false`, preserving the existing unpaid booking flow until the business explicitly enables online payment.

## Webhook requirements
Verify authenticity, tolerate retries, be idempotent, return quickly, and avoid expensive synchronous work.

## Security
Never store raw PAN/CVV. Keep gateway secrets outside source control.

## Adding a provider
Implement PaymentGateway, add provider client/configuration/verification/tests, register it, and leave BookingService/SubscriptionService provider-neutral.


## Current Kashier integration notes

- Hosted payment sessions use Kashier's `/v3/payment/sessions` endpoint with the merchant secret key and Payment API key, returning a `sessionUrl` for hosted checkout.
- Payment verification reads the session payment state server-side; browser redirects are not treated as payment confirmation.
- The webhook endpoint verifies `x-kashier-signature` using the Payment API key and the sorted `signatureKeys` payload rules.
- Webhook processing is idempotent on tenant + provider + transaction ID + transaction status and validates amount/currency before applying the result.
- Kashier credentials are environment variables only and are never stored in source control.
- Redirect signatures use Kashier's fixed parameter order and Payment API Key; missing signed redirect fields are represented as literal `null`.
- Webhook signatures use the separate webhook algorithm documented by Kashier: sorted `signatureKeys`, URL-encoded values, and the Payment API Key.
