<?php

namespace Database\Seeders;

use App\Domain\Module\Models\Module;
use Illuminate\Database\Seeder;

class ModuleSeeder extends Seeder
{
    public function run(): void
    {
        $modules = [
            ['appointments', 'Appointments', 'المواعيد', true],
            ['calendar', 'Calendar', 'التقويم', true],
            ['customers', 'Customers', 'العملاء', true],
            ['services', 'Services', 'الخدمات', true],
            ['staff', 'Staff', 'الموظفون', true],
            ['notifications', 'Notifications', 'الإشعارات', true],
            ['payments', 'Payments', 'المدفوعات', false],
            ['invoices', 'Invoices', 'الفواتير', false],
            ['inventory', 'Inventory', 'المخزون', false],
            ['branches', 'Branches', 'الفروع', false],
        ];

        foreach ($modules as [$key, $en, $ar, $core]) {
            Module::query()->updateOrCreate(
                ['key' => $key],
                [
                    'name' => ['en' => $en, 'ar' => $ar],
                    'is_core' => $core,
                    'is_active' => true,
                ],
            );
        }
    }
}
