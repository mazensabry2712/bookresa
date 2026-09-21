<?php

namespace App\Domain\Scheduling\Models;

use App\Domain\Service\Models\Service;
use App\Domain\Staff\Models\StaffProfile;
use App\Domain\Tenant\Concerns\BelongsToTenant;
use App\Domain\Tenant\Services\CurrentTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use LogicException;

class ServiceStaff extends Model
{
    use HasFactory, BelongsToTenant;

    protected $table = 'service_staff';

    protected $fillable = [
        'tenant_id',
        'service_id',
        'staff_id',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(StaffProfile::class);
    }

    protected static function booted(): void
    {
        static::saving(function (self $assignment): void {
            $tenantId = app(CurrentTenant::class)->idOrFail();

            $serviceTenantId = DB::table('services')
                ->where('id', $assignment->service_id)
                ->value('tenant_id');

            $staffTenantId = DB::table('staff_profiles')
                ->where('id', $assignment->staff_id)
                ->value('tenant_id');

            if ((int) $serviceTenantId !== $tenantId || (int) $staffTenantId !== $tenantId) {
                throw new LogicException('Service and staff must belong to the current tenant.');
            }

            $assignment->tenant_id = $tenantId;
        });
    }
}
