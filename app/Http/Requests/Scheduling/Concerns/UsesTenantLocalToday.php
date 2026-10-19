<?php

namespace App\Http\Requests\Scheduling\Concerns;

use App\Domain\Tenant\Services\CurrentTenant;
use Carbon\CarbonImmutable;

trait UsesTenantLocalToday
{
    protected function tenantLocalToday(): string
    {
        $tenant = app(CurrentTenant::class)->get();
        $timezone = trim((string) data_get($tenant?->profile, 'timezone'));

        if ($timezone === '') {
            $timezone = (string) config('app.timezone', 'UTC');
        }

        return CarbonImmutable::now($timezone)->toDateString();
    }
}
