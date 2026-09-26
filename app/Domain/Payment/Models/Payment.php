<?php

namespace App\Domain\Payment\Models;

use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Tenant\Concerns\BelongsToTenant;
use App\Domain\Tenant\Models\Tenant;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property int $tenant_id
 * @property string $payable_type
 * @property int $payable_id
 * @property string $reference
 * @property string $provider
 * @property string|null $provider_reference
 * @property int $amount_minor
 * @property string $currency
 * @property PaymentStatus $status
 * @property string|null $method
 * @property string|null $checkout_url
 * @property string|null $idempotency_key
 * @property array<string, mixed>|null $metadata
 * @property CarbonInterface|null $paid_at
 * @property CarbonInterface|null $expires_at
 * @property-read Tenant $tenant
 */
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
        'checkout_url',
        'idempotency_key',
        'metadata',
        'paid_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'amount_minor' => 'integer',
            'status' => PaymentStatus::class,
            'metadata' => 'array',
            'paid_at' => 'immutable_datetime',
            'expires_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function payable(): MorphTo
    {
        return $this->morphTo();
    }
}
