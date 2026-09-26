<?php

namespace App\Domain\Service\Actions;

use App\Domain\Service\Models\Service;
use App\Domain\Staff\Models\StaffProfile;
use App\Domain\Tenant\Services\CurrentTenant;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class SyncServiceAssignments
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
    ) {
    }

    /**
     * @param  array<int, int|string>  $serviceIds
     */
    public function handle(StaffProfile $staff, array $serviceIds): StaffProfile
    {
        $tenantId = $this->currentTenant->idOrFail();

        if ((int) $staff->tenant_id !== $tenantId) {
            throw new RuntimeException('Staff member must belong to the current tenant.');
        }

        $ids = collect($serviceIds)
            ->map(fn (int|string $id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();

        $validIds = Service::query()
            ->whereIn('id', $ids)
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        if (count($validIds) !== count($ids)) {
            throw new RuntimeException('All selected services must belong to the current tenant.');
        }

        return DB::transaction(function () use ($staff, $ids, $tenantId): StaffProfile {
            $staff->services()->syncWithPivotValues(
                $ids,
                ['tenant_id' => $tenantId],
            );

            return $staff->fresh(['user', 'services']);
        });
    }
}
