<?php

namespace App\Domain\Booking\Models;

use App\Domain\Booking\Enums\BookingStatus;
use App\Domain\Booking\Enums\PaymentStatus;
use App\Domain\Customer\Models\Customer;
use App\Domain\Payment\Models\Payment;
use App\Domain\Service\Models\Service;
use App\Domain\Staff\Models\StaffProfile;
use App\Domain\Tenant\Concerns\BelongsToTenant;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int $customer_id
 * @property int $service_id
 * @property int|null $staff_id
 * @property CarbonInterface $starts_at
 * @property CarbonInterface $ends_at
 * @property CarbonInterface|null $block_ends_at
 * @property BookingStatus $status
 * @property PaymentStatus $payment_status
 * @property string|null $payment_method
 * @property string $booking_reference
 * @property string|null $notes
 * @property CarbonInterface|null $reminder_sent_at
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read Customer $customer
 * @property-read Service $service
 * @property-read StaffProfile|null $staff
 */
class Booking extends Model
{
    public const EXPIRED_ONLINE_PAYMENT_CANCELLATION_REASON = 'Online payment attempt failed or expired after provider verification; slot released.';

    public const CHECKOUT_CREATION_FAILED_CANCELLATION_REASON = 'Online payment checkout could not be created.';

    public const PAYMENT_RETRY_REOPENED_REASON = 'Customer retried online payment after slot availability was revalidated.';

    public const RETRYABLE_ONLINE_PAYMENT_CANCELLATION_REASONS = [
        self::EXPIRED_ONLINE_PAYMENT_CANCELLATION_REASON,
        self::CHECKOUT_CREATION_FAILED_CANCELLATION_REASON,
    ];

    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'service_id',
        'staff_id',
        'service_name_snapshot',
        'service_price_minor_snapshot',
        'service_currency_snapshot',
        'service_duration_minutes_snapshot',
        'service_buffer_minutes_snapshot',
        'starts_at',
        'ends_at',
        'block_ends_at',
        'status',
        'payment_status',
        'payment_method',
        'booking_reference',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'immutable_datetime',
            'ends_at' => 'immutable_datetime',
            'block_ends_at' => 'immutable_datetime',
            'service_name_snapshot' => 'array',
            'service_price_minor_snapshot' => 'integer',
            'service_duration_minutes_snapshot' => 'integer',
            'service_buffer_minutes_snapshot' => 'integer',
            'reminder_sent_at' => 'immutable_datetime',
            'status' => BookingStatus::class,
            'payment_status' => PaymentStatus::class,
        ];
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return BelongsTo<Service, $this>
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * @return BelongsTo<StaffProfile, $this>
     */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(StaffProfile::class);
    }

    /**
     * @return HasMany<BookingStatusHistory, $this>
     */
    public function statusHistory(): HasMany
    {
        return $this->hasMany(BookingStatusHistory::class);
    }

    /**
     * @return MorphMany<Payment, $this>
     */
    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable');
    }
}
