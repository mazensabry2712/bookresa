<?php

namespace App\Domain\Billing\Models;

use App\Domain\Billing\Enums\UsageChargeType;
use App\Domain\Tenant\Concerns\BelongsToTenant;
use App\Domain\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UsageCharge extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'usage_period_id',
        'type',
        'units',
        'unit_price_minor',
        'amount_minor',
        'pricing_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'type' => UsageChargeType::class,
            'units' => 'integer',
            'unit_price_minor' => 'integer',
            'amount_minor' => 'integer',
            'pricing_snapshot' => 'array',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function usagePeriod(): BelongsTo
    {
        return $this->belongsTo(UsagePeriod::class);
    }
}
