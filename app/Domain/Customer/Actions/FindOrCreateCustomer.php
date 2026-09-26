<?php

namespace App\Domain\Customer\Actions;

use App\Domain\Billing\Services\CustomerUsagePolicy;
use App\Domain\Customer\Models\Customer;
use App\Domain\Customer\Services\CustomerIdentity;
use App\Domain\Tenant\Services\CurrentTenant;
use Carbon\CarbonImmutable;

final class FindOrCreateCustomer
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
        private readonly CustomerIdentity $identity,
    ) {}

    public function handle(
        string $name,
        ?string $phone = null,
        ?string $email = null,
    ): Customer {
        $tenantId = $this->currentTenant->idOrFail();
        $normalizedPhone = $this->identity->normalizePhone($phone);

        $query = Customer::query();

        $customer = $normalizedPhone !== null
            ? $query->where('normalized_phone', $normalizedPhone)->first()
            : null;

        if ($customer !== null) {
            $customer->fill([
                'name' => $name,
                'phone' => $phone,
                'email' => $email,
                'last_seen_at' => CarbonImmutable::now('UTC'),
            ])->save();

            return $customer;
        }

        return Customer::query()->create([
            'tenant_id' => $tenantId,
            'normalized_phone' => $normalizedPhone,
            'name' => $name,
            'phone' => $phone,
            'email' => $email,
            'first_seen_at' => CarbonImmutable::now('UTC'),
            'last_seen_at' => CarbonImmutable::now('UTC'),
        ]);
    }
}
