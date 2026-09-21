<?php

namespace App\Domain\Service\Models;

use App\Domain\Staff\Models\StaffProfile;
use App\Domain\Tenant\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Service extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'price_minor',
        'currency',
        'duration_minutes',
        'buffer_minutes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'name' => 'array',
            'description' => 'array',
            'price_minor' => 'integer',
            'duration_minutes' => 'integer',
            'buffer_minutes' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function staff(): BelongsToMany
    {
        return $this->belongsToMany(
            StaffProfile::class,
            'service_staff',
            'service_id',
            'staff_id',
        )->withTimestamps();
    }
}
