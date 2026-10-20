<?php

namespace App\Domain\Payment\Models;

use App\Domain\Booking\Models\Booking;
use App\Domain\Tenant\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Immutable monthly policy allocation. One qualifying booking consumes one workspace/month ordinal.
 */
class BookingCommissionAllocation extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id',
        'booking_id',
        'period_start',
        'period_end',
        'timezone',
        'booking_ordinal',
        'free_booking_allowance',
        'commission_enabled',
        'eligible',
        'rate_basis_points',
        'booking_amount_minor_snapshot',
        'commission_amount_minor_snapshot',
        'currency',
        'policy_version',
        'qualified_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'immutable_date',
            'period_end' => 'immutable_date',
            'booking_ordinal' => 'integer',
            'free_booking_allowance' => 'integer',
            'commission_enabled' => 'boolean',
            'eligible' => 'boolean',
            'rate_basis_points' => 'integer',
            'booking_amount_minor_snapshot' => 'integer',
            'commission_amount_minor_snapshot' => 'integer',
            'qualified_at' => 'immutable_datetime',
            'metadata' => 'array',
        ];
    }

    /** @return BelongsTo<Booking, $this> */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
