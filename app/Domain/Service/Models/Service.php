<?php

namespace App\Domain\Service\Models;

use App\Domain\Scheduling\Models\ServiceStaff;
use App\Domain\Staff\Models\StaffProfile;
use App\Domain\Tenant\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $tenant_id
 * @property array<string, mixed> $name
 * @property array<string, mixed>|null $description
 * @property int $price_minor
 * @property string $currency
 * @property int $duration_minutes
 * @property int $buffer_minutes
 * @property bool $is_active
 */
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

    /**
     * @return BelongsToMany<StaffProfile, $this>
     */
    public function staff(): BelongsToMany
    {
        return $this->belongsToMany(
            StaffProfile::class,
            'service_staff',
            'service_id',
            'staff_id',
        )->withPivot('tenant_id')->withTimestamps();
    }

    /**
     * @return HasMany<ServiceStaff, $this>
     */
    public function staffAssignments(): HasMany
    {
        return $this->hasMany(ServiceStaff::class);
    }
}
