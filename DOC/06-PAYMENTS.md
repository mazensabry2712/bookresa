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
Provider-neutral references, payable context, amount_minor, currency, status, method, metadata and paid_at.

## Kashier flow
Create payment/session → secure provider checkout → webhook → verify signature → idempotency → persist result → update Booking/Subscription → queue notifications.

A browser redirect alone never marks a payment successful.

## Webhook requirements
Verify authenticity, tolerate retries, be idempotent, return quickly, and avoid expensive synchronous work.

## Security
Never store raw PAN/CVV. Keep gateway secrets outside source control.

## Adding a provider
Implement PaymentGateway, add provider client/configuration/verification/tests, register it, and leave BookingService/SubscriptionService provider-neutral.
