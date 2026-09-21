<?php

namespace App\Domain\Payment\Models;

use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Tenant\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Payment extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'payable_type',
        'payable_id',
        'reference',
        'provider',
        'provider_reference',
        'amount_minor',
        'currency',
        'status',
        'method',
        'idempotency_key',
        'metadata',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount_minor' => 'integer',
            'status' => PaymentStatus::class,
            'metadata' => 'array',
            'paid_at' => 'immutable_datetime',
        ];
    }

    public function payable(): MorphTo
    {
        return $this->morphTo();
    }
}
