<?php

namespace App\Domain\Scheduling\Models;

use App\Domain\Staff\Models\StaffProfile;
use App\Domain\Tenant\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
