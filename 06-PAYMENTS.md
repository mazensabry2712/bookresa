# BookResa — Payment Architecture

## Goal
BookResa owns a provider-neutral Payment Core. The MVP provider is Kashier. Future providers must be addable without rewriting Booking, Subscription or Billing.

## Domain
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
- map provider state to internal state

## Payment contexts
1. Business → BookResa subscription.
2. Customer → Business booking/service.

## Payment record
Provider-independent references, payable context, amount_minor, currency, status, method, metadata and paid_at.

## Kashier flow
Create payment/session → secure checkout → provider webhook → verify signature → idempotency → persist result → update Booking/Subscription → queue notifications.

Browser redirect alone never marks a payment successful.

## Webhook
Must verify authenticity, tolerate retries, be idempotent, return quickly and avoid expensive synchronous work.

## Security
Never store raw PAN/CVV. Keep gateway secrets outside source control.

## New provider
Implement PaymentGateway, add provider client/verification/configuration/tests, register it. Do not modify BookingService or SubscriptionService for provider-specific details.
