<?php

namespace App\Domain\Payment\Models;

use App\Domain\Payment\Enums\TenantPaymentAccountStatus;
use App\Domain\Tenant\Models\Tenant;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $tenant_id
 * @property string $provider
 * @property string|null $merchant_id
 * @property TenantPaymentAccountStatus $status
 * @property CarbonInterface|null $connected_at
 * @property array<string, mixed>|null $metadata
 */
class TenantPaymentAccount extends Model
{
    /** @use HasFactory<\Database\Factories\TenantPaymentAccountFactory> */
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'provider',
        'merchant_id',
        'status',
        'connected_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'status' => TenantPaymentAccountStatus::class,
            'connected_at' => 'immutable_datetime',
            'metadata' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function isActive(): bool
    {
        return $this->status === TenantPaymentAccountStatus::Active
            && filled($this->merchant_id);
    }
}
