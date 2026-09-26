<?php

namespace App\Domain\Business\Models;

use App\Domain\Tenant\Concerns\BelongsToTenant;
use App\Domain\Tenant\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $tenant_id
 * @property array<string, mixed> $name
 * @property array<string, mixed>|null $description
 * @property string|null $logo_path
 * @property string|null $cover_path
 * @property string|null $phone
 * @property string|null $email
 * @property array<string, mixed>|null $location
 * @property array<string, mixed>|null $address
 * @property array<string, mixed>|null $social_links
 * @property string $timezone
 * @property string|null $locale
 * @property array<string, mixed>|null $booking_settings
 */
class BusinessProfile extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'logo_path',
        'cover_path',
        'phone',
        'email',
        'location',
        'address',
        'social_links',
        'timezone',
        'locale',
        'booking_settings',
    ];

    protected function casts(): array
    {
        return [
            'name' => 'array',
            'description' => 'array',
            'social_links' => 'array',
            'booking_settings' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
