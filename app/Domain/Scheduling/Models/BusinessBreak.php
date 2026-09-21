<?php

namespace App\Domain\Scheduling\Models;

use App\Domain\Tenant\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessBreak extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'day_of_week',
        'starts_at',
        'ends_at',
        'label',
    ];

    protected function casts(): array
    {
        return ['day_of_week' => 'integer'];
    }
}
