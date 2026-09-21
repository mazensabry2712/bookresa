<?php

namespace App\Domain\Billing\Models;

use App\Domain\Billing\Enums\SubscriptionStatus;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Payment\Models\Payment;
use App\Domain\Tenant\Concerns\BelongsToTenant;
use App\Domain\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Subscription extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'plan_id',
        'start_at',
        'end_at',
        'status',
        'payment_status',
        'price_minor',
        'currency',
        'billing_period',
        'included_customer_limit',
        'additional_customer_price_minor',
        'pricing_snapshot',
        'next_plan_id',
        'plan_change_effective_at',
        'cancelled_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'start_at' => 'immutable_datetime',
            'end_at' => 'immutable_datetime',
            'status' => SubscriptionStatus::class,
            'payment_status' => PaymentStatus::class,
            'price_minor' => 'integer',
            'included_customer_limit' => 'integer',
            'additional_customer_price_minor' => 'integer',
            'billing_period' => \App\Domain\Billing\Enums\PlanBillingPeriod::class,
            'pricing_snapshot' => 'array',
            'plan_change_effective_at' => 'immutable_datetime',
            'cancelled_at' => 'immutable_datetime',
            'metadata' => 'array',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function nextPlan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'next_plan_id');
    }

    public function usagePeriods(): HasMany
    {
        return $this->hasMany(UsagePeriod::class);
    }

    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable');
    }
}
