<?php

namespace App\Domain\Scheduling\Actions;

use App\Domain\Scheduling\Models\BusinessWorkingHour;
use App\Domain\Tenant\Services\CurrentTenant;
use App\Domain\Scheduling\Enums\DayOfWeek;
use Illuminate\Support\Facades\DB;

final class SetBusinessWorkingHours
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
    ) {
    }

    /**
     * @param array<int, array{day_of_week:int, opens_at:?string, closes_at:?string, is_closed?:bool}> $hours
     */
    public function handle(array $hours): void
    {
        $tenantId = $this->currentTenant->idOrFail();

        DB::transaction(function () use ($hours, $tenantId): void {
            foreach ($hours as $item) {
                $day = DayOfWeek::from((int) $item['day_of_week']);
                $closed = (bool) ($item['is_closed'] ?? false);

                if ($closed) {
                    BusinessWorkingHour::query()->updateOrCreate(
                        ['day_of_week' => $day->value],
                        [
                            'tenant_id' => $tenantId,
                            'opens_at' => $item['opens_at'] ?? '00:00:00',
                            'closes_at' => $item['closes_at'] ?? '00:00:00',
                            'is_closed' => true,
                        ],
                    );

                    continue;
                }

                BusinessWorkingHour::query()->updateOrCreate(
                    ['day_of_week' => $day->value],
                    [
                        'tenant_id' => $tenantId,
                        'opens_at' => $item['opens_at'],
                        'closes_at' => $item['closes_at'],
                        'is_closed' => false,
                    ],
                );
            }
        });
    }
}
