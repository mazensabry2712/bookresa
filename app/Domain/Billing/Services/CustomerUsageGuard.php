<?php

namespace App\Domain\Billing\Services;

use Illuminate\Support\Facades\DB;

final class CustomerUsageGuard
{
    public static function countCustomers(int $tenantId): int
    {
        return (int) DB::table('customers')
            ->where('tenant_id', $tenantId)
            ->count();
    }
}
