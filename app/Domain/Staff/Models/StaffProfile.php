<?php

namespace App\Domain\Staff\Models;

use App\Domain\Service\Models\Service;
use App\Domain\Staff\Enums\StaffStatus;
use App\Domain\Tenant\Concerns\BelongsToTenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(
            Service::class,
            'service_staff',
            'staff_id',
            'service_id',
        )->withTimestamps();
    }

    public function workingHours(): HasMany
    {
        return $this->hasMany(StaffWorkingHour::class, 'staff_id');
    }

    public function daysOff(): HasMany
    {
        return $this->hasMany(StaffDayOff::class, 'staff_id');
    }

    public function availability(): HasMany
    {
        return $this->hasMany(StaffAvailability::class, 'staff_id');
    }
}
