<?php

namespace App\Domain\Business\Actions;

use App\Domain\Business\Models\BusinessProfile;
use App\Domain\Business\Models\BusinessType;
use App\Domain\Identity\Services\TenantRoleProvisioner;
use App\Domain\Module\Models\Module;
use App\Domain\Tenant\Enums\MembershipStatus;
use App\Domain\Tenant\Enums\TenantStatus;
use App\Domain\Tenant\Models\Tenant;
use App\Domain\Tenant\Models\TenantMembership;
use App\Domain\Tenant\Services\CurrentTenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

final class CreateBusiness
{
    public function __construct(
        private readonly TenantRoleProvisioner $roleProvisioner,
        private readonly CurrentTenant $currentTenant,
    ) {
    }

    public function handle(
        User $owner,
        BusinessType $businessType,
        array $data,
    ): Tenant {
        return DB::transaction(function () use ($owner, $businessType, $data): Tenant {
            if (! $businessType->is_active) {
                throw new RuntimeException('The selected business type is not active.');
            }

            $name = trim((string) ($data['name'] ?? ''));

            if ($name === '') {
                throw new RuntimeException('Business name is required.');
            }

            $slug = $this->uniqueSlug((string) ($data['slug'] ?? $name));

            $tenant = Tenant::query()->create([
                'slug' => $slug,
                'business_type_id' => $businessType->getKey(),
                'status' => TenantStatus::Active,
                'settings' => [
                    'onboarding' => [
                        'step' => 'workspace',
                        'completed' => false,
                    ],
                ],
            ]);

            return $this->currentTenant->run($tenant, function () use ($owner, $businessType, $data, $name, $tenant): Tenant {
                BusinessProfile::query()->create([
                    'tenant_id' => $tenant->getKey(),
                    'name' => [
                        'en' => $data['name_en'] ?? $name,
                        'ar' => $data['name_ar'] ?? $name,
                    ],
                    'description' => [
                        'en' => $data['description_en'] ?? null,
                        'ar' => $data['description_ar'] ?? null,
                    ],
                    'phone' => $data['phone'] ?? null,
                    'email' => $data['email'] ?? $owner->email,
                    'timezone' => $data['timezone'] ?? 'UTC',
                    'locale' => $data['locale'] ?? config('bookresa.default_locale', 'en'),
                    'booking_settings' => [
                        'customer_account_required' => false,
                    ],
                ]);

                TenantMembership::query()->create([
                    'tenant_id' => $tenant->getKey(),
                    'user_id' => $owner->getKey(),
                    'status' => MembershipStatus::Active,
                    'is_primary' => ! $owner->tenantMemberships()->where('is_primary', true)->exists(),
                ]);

                $this->roleProvisioner->provisionOwner($tenant);

                $defaultModuleKeys = collect($businessType->default_modules ?? [])
                    ->filter()
                    ->unique()
                    ->values();

                if ($defaultModuleKeys->isNotEmpty()) {
                    $modules = Module::query()
                        ->whereIn('key', $defaultModuleKeys)
                        ->where('is_active', true)
                        ->get(['id', 'key']);

                    $tenant->modules()->sync(
                        $modules->mapWithKeys(fn (Module $module): array => [
                            $module->getKey() => [
                                'enabled' => true,
                            ],
                        ])->all(),
                    );
                }

                $owner->refresh();

                return $tenant->fresh(['profile', 'businessType', 'modules']);
            });
        });
    }

    private function uniqueSlug(string $value): string
    {
        $base = Str::slug($value) ?: 'business';
        $slug = $base;
        $suffix = 2;

        while (Tenant::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
