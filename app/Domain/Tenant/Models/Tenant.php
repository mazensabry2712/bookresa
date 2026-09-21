<?php

namespace App\Domain\Tenant\Models;

use App\Domain\Tenant\Enums\TenantStatus;
use App\Domain\Tenant\Enums\MembershipStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
