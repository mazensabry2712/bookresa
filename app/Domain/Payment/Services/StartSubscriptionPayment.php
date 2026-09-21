<?php

namespace App\Domain\Payment\Services;

use App\Domain\Billing\Enums\SubscriptionStatus;
use App\Domain\Billing\Models\Subscription;
use App\Domain\Business\Models\BusinessProfile;
use App\Domain\Payment\Contracts\PaymentGateway;
use App\Domain\Payment\Models\Payment;
use App\Domain\Tenant\Services\CurrentTenant;
use LogicException;
use RuntimeException;

final class StartSubscriptionPayment
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
        private readonly PaymentService $payments,
        private readonly PaymentGateway $gateway,
    ) {
    }

    public function handle(Subscription $subscription): Payment
    {
        $tenantId = $this->currentTenant->idOrFail();

        if ((int) $subscription->tenant_id !== $tenantId) {
            throw new LogicException('Subscription must belong to the current tenant.');
        }

        $subscription->loadMissing(['plan', 'tenant.profile']);

        if (! in_array($subscription->status, [SubscriptionStatus::Trial, SubscriptionStatus::Active], true)) {
            throw new RuntimeException('This subscription cannot be paid.');
        }

        if ($subscription->status === SubscriptionStatus::Trial) {
            throw new RuntimeException('Trial subscriptions do not require a payment.');
        }

        if ((int) $subscription->price_minor <= 0) {
            throw new RuntimeException('This subscription does not require an online payment.');
        }

        $profile = $subscription->tenant?->profile;

        if (! $profile instanceof BusinessProfile || blank($profile->email)) {
            throw new RuntimeException('Business email is required for subscription payment.');
        }

        $provider = (string) config('bookresa.payments.default_provider', 'kashier');

        return $this->payments->start(
            gateway: $this->gateway,
            payable: $subscription,
            amountMinor: (int) $subscription->price_minor,
            currency: (string) $subscription->currency,
            provider: $provider,
            description: 'BookResa subscription - '.$subscription->pricing_snapshot['name']['en'],
            metadata: [
                'payment_context' => 'subscription',
                'subscription_id' => $subscription->id,
                'plan_id' => $subscription->plan_id,
                'subscription_start' => $subscription->start_at?->toIso8601String(),
                'subscription_end' => $subscription->end_at?->toIso8601String(),
                'customer_reference' => 'tenant-'.$subscription->tenant_id,
                'customer_email' => $profile->email,
                'merchant_redirect' => route('payments.kashier.return'),
            ],
            idempotencyKey: 'subscription-'.$subscription->id.'-'.$provider,
        );
    }
}
