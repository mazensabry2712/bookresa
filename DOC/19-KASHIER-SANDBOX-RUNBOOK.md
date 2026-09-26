# BookResa — Kashier Sandbox Runbook

## Purpose

This runbook verifies the real Kashier test environment without storing credentials in source control.

Kashier's current documentation uses:

- https://test-api.kashier.io/v3/payment/sessions for session creation.
- https://test-api.kashier.io/v3/payment/sessions/{sessionId}/payment for server-side payment verification.
- https://payments.kashier.io/session/{sessionId}?mode=test for hosted checkout.

The browser redirect is not treated as payment confirmation; BookResa verifies the payment server-side and also reconciles signed webhooks.

## Required environment

Set these values only in the local .env file or deployment secret store:

    KASHIER_MODE=test
    KASHIER_BASE_URL=https://test-api.kashier.io
    KASHIER_MERCHANT_ID=YOUR_TEST_MID
    KASHIER_API_KEY=YOUR_TEST_API_KEY
    KASHIER_SECRET_KEY=YOUR_TEST_SECRET_KEY
    KASHIER_MERCHANT_REDIRECT=https://YOUR-PUBLIC-BOOKRESA-HOST/payment/kashier/return
    KASHIER_SERVER_WEBHOOK=https://YOUR-PUBLIC-BOOKRESA-HOST/webhooks/kashier

    KASHIER_E2E=true
    KASHIER_E2E_CUSTOMER_EMAIL=test@example.com
    KASHIER_E2E_CUSTOMER_REFERENCE=bookresa-e2e-customer

Do not commit these values. Use the test credentials from the Kashier dashboard.

## Step 1 — Run the real API smoke test

From the BookResa project:

    cd C:\Herd\bookresa
    php artisan test --compact --filter=KashierSandboxSmokeTest

Expected result:

- A real test-mode payment session is created.
- Kashier returns a hosted session URL.
- BookResa can read the created session with the server-side verification endpoint.
- The observed initial status is normally PENDING before the shopper completes checkout.

This test creates a real sandbox session but does not charge real money.

## Step 2 — Complete a real sandbox payment manually

Open the returned checkout URL from the test output and complete a Kashier test payment using the current test payment credentials/cards from Kashier's test environment.

Verify all of the following:

1. The browser returns to KASHIER_MERCHANT_REDIRECT.
2. The signed redirect is accepted by BookResa.
3. BookResa performs server-side payment verification.
4. Kashier sends the webhook to KASHIER_SERVER_WEBHOOK.
5. The x-kashier-signature is accepted.
6. The payment reaches the expected final state.
7. For a booking payment, the booking payment status is synchronized.
8. For a subscription payment, the subscription payment status and entitlement are synchronized.

Kashier states that webhook endpoints must be publicly reachable and unauthenticated, and that HTTP 200 acknowledges a new delivery while HTTP 409 acknowledges an already-processed event.

## Step 3 — Repeat the webhook once

Replay the same webhook payload once and verify BookResa returns HTTP 409 and does not apply the payment twice.

## Step 4 — Refund test

After a successful sandbox payment, run one refund through the configured operational path and verify that BookResa stores the refund result correctly.

Kashier's current refund endpoint is:

    PUT /v3/payment/refund/{orderId}

using the merchant secret key.

## Completion rule

Do not mark the BookResa checklist item real Kashier sandbox end-to-end verification as complete until Step 2 through Step 4 have been performed against the real Kashier test account.

A passing automated suite with HTTP fakes is not equivalent to a real sandbox verification.