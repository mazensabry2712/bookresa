<?php

namespace App\Domain\Billing\Models;

use App\Domain\Billing\Enums\PlanBillingPeriod;
use App\Domain\Billing\Enums\SubscriptionStatus;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Payment\Models\Payment;
use App\Domain\Tenant\Concerns\BelongsToTenant;
use App\Domain\Tenant\Models\Tenant;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int $plan_id
 * @property CarbonInterface $start_at
 * @property CarbonInterface $end_at
 * @property SubscriptionStatus $status
 * @property PaymentStatus $payment_status
 * @property int $price_minor
 * @property string $currency
 * @property PlanBillingPeriod $billing_period
 * @property int $included_customer_limit
 * @property int $additional_customer_price_minor
 * @property array<string, mixed>|null $pricing_snapshot
 * @property int|null $next_plan_id
 * @property CarbonInterface|null $plan_change_effective_at
 * @property CarbonInterface|null $cancelled_at
 * @property array<string, mixed>|null $metadata
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 */
class Subscription extends Model
{
    use BelongsToTenant, HasFactory;

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
            'billing_period' => PlanBillingPeriod::class,
            'pricing_snapshot' => 'array',
            'plan_change_effective_at' => 'immutable_datetime',
            'cancelled_at' => 'immutable_datetime',
            'metadata' => 'array',
        ];
    }

    public function isUsable(): bool
    {
        if (! $this->status->isUsable()) {
            return false;
        }

        return $this->status === SubscriptionStatus::Trial
            || $this->payment_status === PaymentStatus::Paid;
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * @return BelongsTo<Plan, $this>
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * @return BelongsTo<Plan, $this>
     */
    public function nextPlan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'next_plan_id');
    }

    /**
     * @return HasMany<UsagePeriod, $this>
     */
    public function usagePeriods(): HasMany
    {
        return $this->hasMany(UsagePeriod::class);
    }

    /**
     * @return MorphMany<Payment, $this>
     */
    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable');
    }
}
