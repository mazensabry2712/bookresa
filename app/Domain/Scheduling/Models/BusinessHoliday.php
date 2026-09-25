<?php

namespace App\Domain\Scheduling\Models;

use App\Domain\Tenant\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessHoliday extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'holiday_date',
        'reason',
    ];

    protected function casts(): array
    {
        return ['holiday_date' => 'date'];
    }
}
