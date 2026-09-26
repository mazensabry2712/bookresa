<?php

namespace App\Domain\Customer\Actions;

use App\Domain\Customer\Models\Customer;
use App\Domain\Customer\Services\CustomerIdentity;
use App\Domain\Tenant\Services\CurrentTenant;
use LogicException;
use RuntimeException;

final class UpdateCustomer
{
    public function __construct(private readonly CurrentTenant $currentTenant, private readonly CustomerIdentity $identity) {}

    public function handle(Customer $customer, array $data): Customer
    {
        $tenantId = $this->currentTenant->idOrFail();

        if ((int) $customer->tenant_id !== $tenantId) {
            throw new LogicException('Customer must belong to the current tenant.');
        }

        $phone = $data['phone'] ?? null;
        $normalizedPhone = $this->identity->normalizePhone($phone);

        if (
            $normalizedPhone !== null
            && Customer::query()
                ->where('normalized_phone', $normalizedPhone)
                ->where($customer->getKeyName(), '!=', $customer->getKey())
                ->exists()
        ) {
            throw new RuntimeException('A customer with this phone number already exists.');
        }

        $customer->fill([
            'normalized_phone' => $normalizedPhone,
            'name' => $data['name'],
            'phone' => $phone,
            'email' => $data['email'] ?? null,
        ])->save();

        return $customer->fresh();
    }
}
