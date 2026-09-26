<?php

namespace App\Domain\Customer\Actions;

use App\Domain\Billing\Services\CustomerUsagePolicy;
use App\Domain\Customer\Models\Customer;
use App\Domain\Customer\Services\CustomerIdentity;
use App\Domain\Tenant\Services\CurrentTenant;
use Carbon\CarbonImmutable;
use RuntimeException;

final class CreateCustomer
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
        private readonly CustomerIdentity $identity,
        private readonly CustomerUsagePolicy $usagePolicy,
    ) {}

    public function handle(array $data): Customer
    {
        $tenantId = $this->currentTenant->idOrFail();
        $phone = $data['phone'] ?? null;
        $normalizedPhone = $this->identity->normalizePhone($phone);

        if (
            $normalizedPhone !== null
            && Customer::query()->where('normalized_phone', $normalizedPhone)->exists()
        ) {
            throw new RuntimeException('A customer with this phone number already exists.');
        }

        if (! $this->usagePolicy->allowsCreation()) {
            throw new RuntimeException('The included customer limit has been reached.');
        }

        $now = CarbonImmutable::now('UTC');

        return Customer::query()->create([
            'tenant_id' => $tenantId,
            'normalized_phone' => $normalizedPhone,
            'name' => $data['name'],
            'phone' => $phone,
            'email' => $data['email'] ?? null,
            'first_seen_at' => $now,
            'last_seen_at' => $now,
        ]);
    }
}
