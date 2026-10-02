<?php

namespace App\Domain\Tenant\Models;

use App\Domain\Billing\Models\Subscription;
use App\Domain\Booking\Models\Booking;
use App\Domain\Business\Models\BusinessProfile;
use App\Domain\Business\Models\BusinessType;
use App\Domain\Customer\Models\Customer;
use App\Domain\Module\Models\Module;
use App\Domain\Module\Models\TenantModule;
use App\Domain\Payment\Models\TenantPaymentAccount;
use App\Domain\Service\Models\Service;
use App\Domain\Staff\Models\StaffProfile;
use App\Domain\Tenant\Enums\MembershipStatus;
use App\Domain\Tenant\Enums\TenantStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Multitenancy\Concerns\UsesMultitenancyConfig;
use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Multitenancy\Models\Concerns\ImplementsTenant;

/**
 * @property int $id
 * @property string $slug
 * @property int|null $business_type_id
 * @property TenantStatus $status
 * @property array<string, mixed>|null $settings
 * @property-read BusinessProfile|null $profile
 * @property-read BusinessType|null $businessType
 * @property-read \Illuminate\Support\Collection<int, TenantMembership> $memberships
 */
class Tenant extends Model implements IsTenant
{
    use HasFactory;
    use ImplementsTenant;
    use SoftDeletes;
    use UsesMultitenancyConfig;

    protected $fillable = [
        'slug',
        'business_type_id',
        'status',
        'settings',
    ];

    public function getDatabaseName(): string
    {
        $connection = (string) config('database.default', 'mysql');

        return (string) config("database.connections.{$connection}.database");
    }

    protected function casts(): array
    {
        return [
            'status' => TenantStatus::class,
            'settings' => 'array',
            'deleted_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<BusinessType, $this> */
    public function businessType(): BelongsTo
    {
        return $this->belongsTo(BusinessType::class);
    }

    /** @return HasOne<BusinessProfile, $this> */
    public function profile(): HasOne
    {
        return $this->hasOne(BusinessProfile::class);
    }

    /** @return HasMany<Service, $this> */
    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    /** @return HasMany<StaffProfile, $this> */
    public function staffProfiles(): HasMany
    {
        return $this->hasMany(StaffProfile::class);
    }

    /** @return HasMany<Customer, $this> */
    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    /** @return HasMany<Booking, $this> */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /** @return HasMany<TenantModule, $this> */
    public function tenantModules(): HasMany
    {
        return $this->hasMany(TenantModule::class);
    }

    /** @return BelongsToMany<Module, $this> */
    public function modules(): BelongsToMany
    {
        return $this->belongsToMany(Module::class, 'tenant_modules')
            ->withPivot(['enabled', 'settings'])->withTimestamps();
    }

    /** @return HasMany<Subscription, $this> */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /** @return HasOne<TenantPaymentAccount, $this> */
    public function paymentAccount(): HasOne
    {
        return $this->hasOne(TenantPaymentAccount::class);
    }

    /** @return HasMany<TenantMembership, $this> */
    public function memberships(): HasMany
    {
        return $this->hasMany(TenantMembership::class);
    }

    /** @return BelongsToMany<User, $this> */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tenant_memberships')
            ->wherePivot('status', MembershipStatus::Active->value);
    }
}
