<?php

namespace App\Domain\Business\Actions;

use App\Domain\Business\Models\BusinessProfile;
use App\Domain\Tenant\Services\CurrentTenant;
use App\Support\PlatformSettings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use LogicException;

final class UpdateBusinessProfile
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
        private readonly PlatformSettings $platformSettings,
    ) {}

    public function handle(array $data): BusinessProfile
    {
        $tenantId = $this->currentTenant->idOrFail();
        $centralizedPaymentsEnabled = filter_var(
            $this->platformSettings->get('centralized_booking_payments_enabled', false),
            FILTER_VALIDATE_BOOL,
        );
        return DB::transaction(function () use ($data, $tenantId, $centralizedPaymentsEnabled): BusinessProfile {
            $profile = BusinessProfile::query()->first();

            if ($profile === null) {
                throw new LogicException('The current tenant has no business profile.');
            }

            if ((int) $profile->tenant_id !== $tenantId) {
                throw new LogicException('Business profile must belong to the current tenant.');
            }

            $paymentMethodMode = (string) (
                $data['payment_method_mode']
                ?? data_get($profile->booking_settings, 'payment_method_mode')
                ?? 'both'
            );

            if (!in_array($paymentMethodMode, ['offline_only', 'online_only', 'both'], true)) {
                $paymentMethodMode = 'both';
            }

            $paymentMode = $paymentMethodMode === 'online_only' ? 'full' : 'pay_later';
            $paymentRequired = $paymentMethodMode === 'online_only';
            $customerEmailRequired = (
                $centralizedPaymentsEnabled && $paymentMethodMode !== 'offline_only'
            ) || (bool) ($data['customer_email_required'] ?? false);

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

            if (($data['logo'] ?? null) instanceof UploadedFile) {
                if ($logoPath) {
                    Storage::disk('public')->delete($logoPath);
                }
                $logoPath = $data['logo']->store('businesses/'.$tenantId.'/logo', 'public');
            }

            if (($data['cover'] ?? null) instanceof UploadedFile) {
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
                    'ar' => ($data['name_ar'] ?? null) ?: $data['name_en'],
                ],
                'description' => [
                    'en' => ($data['description_en'] ?? null) ?: null,
                    'ar' => ($data['description_ar'] ?? null) ?: null,
                ],
                'phone' => ($data['phone'] ?? null) ?: null,
                'email' => ($data['email'] ?? null) ?: null,
                'location' => ($data['location'] ?? null) ?: null,
                'address' => ($data['address'] ?? null) ?: null,
                'social_links' => [
                    'website' => ($data['website'] ?? null) ?: null,
                    'facebook' => ($data['facebook'] ?? null) ?: null,
                    'instagram' => ($data['instagram'] ?? null) ?: null,
                ],
                'timezone' => $data['timezone'],
                'locale' => $data['locale'],
                'booking_settings' => array_merge($profile->booking_settings ?? [], [
                    'payment_method_mode' => $paymentMethodMode,
                    'payment_mode' => $paymentMode,
                    'payment_required' => $paymentRequired,
                    'deposit_percent' => null,
                    'customer_email_required' => $customerEmailRequired,
                    'customer_limit_policy' => $data['customer_limit_policy'] ?? data_get($profile->booking_settings, 'customer_limit_policy', 'allow_overage'),
                    'minimum_notice_minutes' => (int) ($data['minimum_notice_minutes'] ?? data_get($profile->booking_settings, 'minimum_notice_minutes', 0)),
                    'maximum_advance_days' => array_key_exists('maximum_advance_days', $data)
                        ? ($data['maximum_advance_days'] === null || $data['maximum_advance_days'] === ''
                            ? null
                            : (int) $data['maximum_advance_days'])
                        : data_get($profile->booking_settings, 'maximum_advance_days'),
                ]),
            ])->save();

            return $profile->fresh();
        });
    }
}
