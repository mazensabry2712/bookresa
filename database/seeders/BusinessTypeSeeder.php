<?php

namespace Database\Seeders;

use App\Domain\Business\Models\BusinessType;
use Illuminate\Database\Seeder;

class BusinessTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            'clinic' => ['Clinic', 'عيادة'],
            'dental-clinic' => ['Dental Clinic', 'عيادة أسنان'],
            'beauty-salon' => ['Beauty Salon', 'صالون تجميل'],
            'barber' => ['Barber', 'حلاق'],
            'gym' => ['Gym', 'جيم'],
            'training-center' => ['Training Center', 'مركز تدريب'],
            'tutor' => ['Tutor', 'مدرس'],
            'consultant' => ['Consultant', 'استشاري'],
            'photography' => ['Photography', 'تصوير'],
            'repair-service' => ['Repair Service', 'خدمات صيانة'],
            'agency' => ['Agency', 'وكالة'],
        ];

        $defaults = [
            'appointments',
            'calendar',
            'customers',
            'services',
            'staff',
            'notifications',
        ];

        foreach ($types as $slug => [$en, $ar]) {
            BusinessType::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => ['en' => $en, 'ar' => $ar],
                    'default_modules' => $defaults,
                    'is_active' => true,
                ],
            );
        }
    }
}
