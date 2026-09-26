<?php

namespace App\Domain\Scheduling\Models;

use App\Domain\Staff\Models\StaffProfile;
use App\Domain\Tenant\Concerns\BelongsToTenant;
use App\Domain\Scheduling\Enums\DayOfWeek;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


use Carbon\CarbonInterface;
use App\Domain\Scheduling\Enums\DayOfWeek;
/**
 * @property int $id
 * @property int $tenant_id
 * @property int $staff_id
 * @property DayOfWeek $day_of_week
 * @property string $opens_at
 * @property string $closes_at
 * @property bool $is_closed
 */

class StaffWorkingHour extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'staff_id',
        'day_of_week',
        'opens_at',
        'closes_at',
        'is_closed',
    ];

    protected function casts(): array
    {
        return [
            'day_of_week' => DayOfWeek::class,
            'is_closed' => 'boolean',
        ];
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(StaffProfile::class);
    }
}
