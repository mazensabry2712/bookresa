<?php

namespace App\Domain\Payment\Models;

use App\Domain\Customer\Models\Customer;
use App\Domain\Tenant\Models\Tenant;
use App\Domain\Tenant\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingCommission extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id',
        'payment_id',
        'booking_commission_allocation_id',
        'booking_ordinal',
        'free_booking_allowance',
        'commission_policy_version',
        'customer_id',
        'customer_ordinal',
        'included_customer_limit',
        'eligible',
        'rate_basis_points',
        'gross_amount_minor',
        'refunded_amount_minor',
        'commission_amount_minor',
        'workspace_net_minor',
        'currency',
        'status',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'booking_commission_allocation_id' => 'integer',
            'booking_ordinal' => 'integer',
            'free_booking_allowance' => 'integer',
            'customer_id' => 'integer',
            'customer_ordinal' => 'integer',
            'included_customer_limit' => 'integer',
            'eligible' => 'boolean',
            'rate_basis_points' => 'integer',
            'gross_amount_minor' => 'integer',
            'refunded_amount_minor' => 'integer',
            'commission_amount_minor' => 'integer',
            'workspace_net_minor' => 'integer',
            'metadata' => 'array',
        ];
    }

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /** @return BelongsTo<Payment, $this> */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** @return BelongsTo<BookingCommissionAllocation, $this> */
    public function allocation(): BelongsTo
    {
        return $this->belongsTo(BookingCommissionAllocation::class, 'booking_commission_allocation_id');
    }
}
