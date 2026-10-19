<?php

namespace App\Domain\Payment\Models;

use App\Domain\Tenant\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Platform-managed payout records. Creating a row reserves balance but never initiates a transfer.
 */
class WorkspacePayout extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'reference',
        'currency',
        'amount_minor',
        'status',
        'payout_method',
        'payout_reference',
        'notes',
        'created_by',
        'paid_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'amount_minor' => 'integer',
            'paid_at' => 'immutable_datetime',
            'metadata' => 'array',
        ];
    }

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /** @return BelongsTo<User, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
