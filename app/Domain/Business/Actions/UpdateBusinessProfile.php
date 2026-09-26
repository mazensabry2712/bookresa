<?php

namespace App\Domain\Business\Actions;

use App\Domain\Business\Models\BusinessProfile;
use App\Domain\Tenant\Services\CurrentTenant;
use Illuminate\Support\Facades\DB;
use LogicException;

final class UpdateBusinessProfile
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
    ) {
    }

    public function handle(array $data): BusinessProfile
    {
        $tenantId = $this->currentTenant->idOrFail();

        return DB::transaction(function () use ($data, $tenantId): BusinessProfile {
            $profile = BusinessProfile::query()->first();

            if ($profile === null) {
                throw new LogicException('The current tenant has no business profile.');
            }

            if ((int) $profile->tenant_id !== $tenantId) {
                throw new LogicException('Business profile must belong to the current tenant.');
            }

            $profile->fill([
                'name' => [
                    'en' => $data['name_en'],
                    'ar' => $data['name_ar'] ?: $data['name_en'],
                ],
                'description' => [
                    'en' => $data['description_en'] ?: null,
                    'ar' => $data['description_ar'] ?: null,
                ],
                'phone' => $data['phone'] ?: null,
                'email' => $data['email'] ?: null,
                'location' => $data['location'] ?: null,
                'address' => $data['address'] ?: null,
                'social_links' => [
                    'website' => $data['website'] ?: null,
                    'facebook' => $data['facebook'] ?: null,
                    'instagram' => $data['instagram'] ?: null,
                ],
                'timezone' => $data['timezone'],
                'locale' => $data['locale'],
            ])->save();

            return $profile->fresh();
        });
    }
}
