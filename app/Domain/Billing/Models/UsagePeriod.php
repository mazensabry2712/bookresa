<?php

namespace App\Domain\Billing\Models;

use App\Domain\Billing\Enums\UsagePeriodStatus;
use App\Domain\Tenant\Concerns\BelongsToTenant;
use App\Domain\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UsagePeriod extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'subscription_id',
        'period_start',
        'period_end',
        'unique_customer_count',
        'included_customer_limit',
        'additional_customer_count',
        'additional_customer_price_minor',
        'base_price_minor',
        'usage_charge_minor',
        'total_charge_minor',
        'currency',
        'status',
        'pricing_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'immutable_datetime',
            'period_end' => 'immutable_datetime',
            'unique_customer_count' => 'integer',
            'included_customer_limit' => 'integer',
            'additional_customer_count' => 'integer',
            'additional_customer_price_minor' => 'integer',
            'base_price_minor' => 'integer',
            'usage_charge_minor' => 'integer',
            'total_charge_minor' => 'integer',
            'status' => UsagePeriodStatus::class,
            'pricing_snapshot' => 'array',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function charges(): HasMany
    {
        return $this->hasMany(UsageCharge::class);
    }
}
