<?php

namespace App\Domain\Scheduling\Models;

use App\Domain\Scheduling\Enums\DayOfWeek;
use App\Domain\Tenant\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $tenant_id
 * @property DayOfWeek $day_of_week
 * @property string $opens_at
 * @property string $closes_at
 * @property bool $is_closed
 */
class BusinessWorkingHour extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id',
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
}
