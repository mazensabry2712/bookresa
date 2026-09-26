<?php

namespace App\Domain\Scheduling\Models;

use App\Domain\Staff\Models\StaffProfile;
use App\Domain\Tenant\Concerns\BelongsToTenant;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


use Carbon\CarbonInterface;
/**
 * @property int $id
 * @property int $tenant_id
 * @property int $staff_id
 * @property CarbonInterface $available_date
 * @property string $starts_at
 * @property string $ends_at
 */

class StaffAvailability extends Model
{
    use HasFactory, BelongsToTenant;

    protected $table = 'staff_availability';

    protected $fillable = [
        'tenant_id',
        'staff_id',
        'available_date',
        'starts_at',
        'ends_at',
    ];

    protected function casts(): array
    {
        return ['available_date' => 'date'];
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(StaffProfile::class);
    }
}
