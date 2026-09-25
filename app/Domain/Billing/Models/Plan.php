<?php

namespace App\Domain\Billing\Models;

use App\Domain\Billing\Enums\PlanBillingPeriod;
use App\Domain\Module\Models\Module;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price_minor',
        'currency',
        'billing_period',
        'included_customer_limit',
        'additional_customer_price_minor',
        'trial_days',
        'is_active',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'name' => 'array',
            'description' => 'array',
            'price_minor' => 'integer',
            'included_customer_limit' => 'integer',
            'additional_customer_price_minor' => 'integer',
            'trial_days' => 'integer',
            'is_active' => 'boolean',
            'billing_period' => PlanBillingPeriod::class,
            'metadata' => 'array',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function modules(): BelongsToMany
    {
        return $this->belongsToMany(Module::class, 'plan_modules')
            ->withPivot(['settings'])
            ->withTimestamps();
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}
