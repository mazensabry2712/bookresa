<?php

namespace App\Domain\Scheduling\Models;

use App\Domain\Tenant\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpecialWorkingHour extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'work_date',
        'opens_at',
        'closes_at',
        'is_closed',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'work_date' => 'date',
            'is_closed' => 'boolean',
        ];
    }
}
