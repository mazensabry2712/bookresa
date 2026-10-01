<?php

namespace Database\Factories;

use App\Domain\Payment\Enums\TenantPaymentAccountStatus;
use App\Domain\Payment\Models\TenantPaymentAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TenantPaymentAccount>
 */
class TenantPaymentAccountFactory extends Factory
{
    protected $model = TenantPaymentAccount::class;

    public function definition(): array
    {
        return [
            'provider' => 'kashier',
            'merchant_id' => 'MID-'.$this->faker->numerify('###-###'),
            'status' => TenantPaymentAccountStatus::Pending,
            'connected_at' => null,
            'metadata' => null,
        ];
    }

    public function active(): static
    {
        return $this->state([
            'status' => TenantPaymentAccountStatus::Active,
            'connected_at' => now(),
        ]);
    }
}
