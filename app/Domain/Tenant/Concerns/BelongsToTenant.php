<?php

namespace App\Domain\Tenant\Concerns;

use App\Domain\Tenant\Services\CurrentTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::creating(function (Model $model): void {
            $model->setAttribute(
                'tenant_id',
                app(CurrentTenant::class)->idOrFail(),
            );
        });

        static::addGlobalScope('tenant', function (Builder $builder): void {
            $tenantId = app(CurrentTenant::class)->id();
            $column = $builder->getModel()->qualifyColumn('tenant_id');

            if ($tenantId === null) {
                $builder->whereRaw('1 = 0');

                return;
            }

            $builder->where($column, $tenantId);
        });
    }

    public function tenant(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Domain\Tenant\Models\Tenant::class);
    }
}
