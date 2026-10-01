<?php

namespace App\Domain\Customer\Models;

use App\Domain\Booking\Models\Booking;
use App\Domain\Tenant\Concerns\BelongsToTenant;
use App\Domain\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;

class Customer extends Model
{
    use BelongsToTenant;
    use HasFactory;
    use Notifiable;

    protected $fillable = [
        'tenant_id',
        'normalized_phone',
        'name',
        'phone',
        'email',
        'is_vip',
        'metadata',
        'first_seen_at',
        'last_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'is_vip' => 'boolean',
            'metadata' => 'array',
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
