<?php

namespace App\Domain\Tenant\Models;

use App\Domain\Tenant\Enums\TenantStatus;
use App\Domain\Tenant\Enums\MembershipStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Domain\Business\Models\BusinessProfile;
use App\Domain\Business\Models\BusinessType;
use App\Domain\Module\Models\Module;
use App\Domain\Module\Models\TenantModule;
use App\Domain\Service\Models\Service;
use App\Domain\Staff\Models\StaffProfile;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'business_type_id',
        'status',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'status' => TenantStatus::class,
            'settings' => 'array',
        ];
    }

    public function businessType(): BelongsTo
    {
        return $this->belongsTo(BusinessType::class);
    }

    public function profile(): HasOne
    {
        return $this->hasOne(BusinessProfile::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function staffProfiles(): HasMany
    {
        return $this->hasMany(StaffProfile::class);
    }

    public function tenantModules(): HasMany
    {
        return $this->hasMany(TenantModule::class);
    }

    public function modules(): BelongsToMany
    {
        return $this->belongsToMany(
            Module::class,
            'tenant_modules',
        )->withPivot(['enabled', 'settings'])->withTimestamps();
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(TenantMembership::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            \App\Models\User::class,
            'tenant_memberships',
        )->wherePivot('status', MembershipStatus::Active->value);
    }
}
