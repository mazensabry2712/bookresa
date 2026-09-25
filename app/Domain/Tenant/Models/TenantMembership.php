<?php

namespace App\Domain\Tenant\Models;

use App\Domain\Tenant\Enums\MembershipStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantMembership extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'status',
        'is_primary',
    ];

    protected function casts(): array
    {
        return [
            'status' => MembershipStatus::class,
            'is_primary' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
