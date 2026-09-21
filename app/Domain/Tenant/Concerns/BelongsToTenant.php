<?php

namespace App\Domain\Tenant\Concerns;

use App\Domain\Tenant\Services\CurrentTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use LogicException;

trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder): void {
            $tenantId = app(CurrentTenant::class)->id();

            if ($tenantId === null) {
                $builder->whereRaw('1 = 0');

                return;
            }

            $builder->where(
                $builder->getModel()->getTable().'.tenant_id',
                $tenantId,
            );
        });

        static::creating(function (Model $model): void {
            $model->setAttribute('tenant_id', app(CurrentTenant::class)->idOrFail());
        });

        static::saving(function (Model $model): void {
            $tenantId = app(CurrentTenant::class)->idOrFail();

            if ($model->exists) {
                $originalTenantId = (int) $model->getOriginal('tenant_id');
                $modelTenantId = (int) $model->getAttribute('tenant_id');

                if ($originalTenantId !== $tenantId || $modelTenantId !== $tenantId) {
                    throw new LogicException('A tenant-owned model cannot be moved across tenants.');
                }

                return;
            }

            $model->setAttribute('tenant_id', $tenantId);
        });

        static::deleting(function (Model $model): void {
            $tenantId = app(CurrentTenant::class)->idOrFail();

            if ((int) $model->getAttribute('tenant_id') !== $tenantId) {
                throw new LogicException('A tenant-owned model cannot be deleted outside its tenant.');
            }
        });
    }

    public function tenant()
    {
        return $this->belongsTo(
            \App\Domain\Tenant\Models\Tenant::class,
        );
    }
}
