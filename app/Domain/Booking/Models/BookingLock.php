<?php

namespace App\Domain\Booking\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookingLock extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'scope_key',
        'lock_date',
    ];

    protected function casts(): array
    {
        return ['lock_date' => 'date'];
    }
}
