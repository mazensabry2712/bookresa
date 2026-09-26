<?php

namespace App\Domain\Staff\Models;

use App\Domain\Scheduling\Models\ServiceStaff;
use App\Domain\Scheduling\Models\StaffAvailability;
use App\Domain\Scheduling\Models\StaffDayOff;
use App\Domain\Scheduling\Models\StaffWorkingHour;
use App\Domain\Service\Models\Service;
use App\Domain\Staff\Enums\StaffStatus;
use App\Domain\Tenant\Concerns\BelongsToTenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;


/**
 * @property int $id
 * @property int $tenant_id
 * @property int $user_id
 * @property string $display_name
 * @property string|null $phone
 * @property string|null $job_title
 * @property string|null $avatar_path
 * @property StaffStatus $status
 * @property array<string, mixed>|null $settings
 * @property-read User $user
 */

class StaffProfile extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'display_name',
        'phone',
        'job_title',
        'avatar_path',
        'status',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'status' => StaffStatus::class,
            'settings' => 'array',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsToMany<Service, $this> */
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(
            Service::class,
            'service_staff',
            'staff_id',
            'service_id',
        )->withPivot('tenant_id')->withTimestamps();
    }

    /** @return HasMany<ServiceStaff, $this> */
    public function serviceAssignments(): HasMany
    {
        return $this->hasMany(ServiceStaff::class, 'staff_id');
    }

    /** @return HasMany<StaffWorkingHour, $this> */
    public function workingHours(): HasMany
    {
        return $this->hasMany(StaffWorkingHour::class, 'staff_id');
    }

    /** @return HasMany<StaffDayOff, $this> */
    public function daysOff(): HasMany
    {
        return $this->hasMany(StaffDayOff::class, 'staff_id');
    }

    /** @return HasMany<StaffAvailability, $this> */
    public function availability(): HasMany
    {
        return $this->hasMany(StaffAvailability::class, 'staff_id');
    }
}
