<?php

namespace App\Domain\Business\Actions;

use App\Domain\Business\Models\BusinessProfile;
use App\Domain\Module\Services\TenantModuleAccess;
use App\Domain\Tenant\Services\CurrentTenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use LogicException;

final class UpdateBusinessProfile
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
        private readonly TenantModuleAccess $moduleAccess,
    ) {
    }

    public function handle(array $data): BusinessProfile
    {
        $tenantId = $this->currentTenant->idOrFail();

        if (in_array($data['payment_mode'], ['full', 'deposit'], true) && ! $this->moduleAccess->allows('payments')) {
            throw new LogicException('Customer payments are not enabled for this workspace.');
        }

        return DB::transaction(function () use ($data, $tenantId): BusinessProfile {
            $profile = BusinessProfile::query()->first();

            if ($profile === null) {
                throw new LogicException('The current tenant has no business profile.');
            }

            if ((int) $profile->tenant_id !== $tenantId) {
                throw new LogicException('Business profile must belong to the current tenant.');
            }

            $logoPath = $profile->logo_path;
            $coverPath = $profile->cover_path;

            if (($data['remove_logo'] ?? false) && $logoPath) {
                Storage::disk('public')->delete($logoPath);
                $logoPath = null;
            }

            if (($data['remove_cover'] ?? false) && $coverPath) {
                Storage::disk('public')->delete($coverPath);
                $coverPath = null;
            }

            if (($data['logo'] ?? null) instanceof \Illuminate\Http\UploadedFile) {
                if ($logoPath) {
                    Storage::disk('public')->delete($logoPath);
                }
                $logoPath = $data['logo']->store('businesses/'.$tenantId.'/logo', 'public');
            }

            if (($data['cover'] ?? null) instanceof \Illuminate\Http\UploadedFile) {
                if ($coverPath) {
                    Storage::disk('public')->delete($coverPath);
                }
                $coverPath = $data['cover']->store('businesses/'.$tenantId.'/cover', 'public');
            }

            $profile->fill([
                'logo_path' => $logoPath,
                'cover_path' => $coverPath,
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
                'booking_settings' => array_merge($profile->booking_settings ?? [], [
                    'payment_mode' => $data['payment_mode'],
                    'payment_required' => in_array($data['payment_mode'], ['full', 'deposit'], true),
                    'deposit_percent' => $data['payment_mode'] === 'deposit' ? (int) $data['deposit_percent'] : null,
                    'customer_email_required' => (bool) ($data['customer_email_required'] ?? false),
                    'customer_limit_policy' => $data['customer_limit_policy'],
                ]),
            ])->save();

            return $profile->fresh();
        });
    }
}
