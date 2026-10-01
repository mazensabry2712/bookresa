<?php

namespace Database\Seeders;

use App\Domain\Platform\Models\PlatformAdmin;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PlatformAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = strtolower(trim((string) env('BOOKRESA_SUPER_ADMIN_EMAIL', 'admin@bookresa.com')));
        $password = (string) env('BOOKRESA_SUPER_ADMIN_PASSWORD', 'ChangeMe@BookResa2026!');

        if ($email === '' || $password === '') {
            throw new \RuntimeException('BOOKRESA_SUPER_ADMIN_EMAIL and BOOKRESA_SUPER_ADMIN_PASSWORD are required.');
        }

        $user = User::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => 'BookResa Super Admin',
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ],
        );

        if ($user->email_verified_at === null) {
            $user->forceFill(['email_verified_at' => now()])->saveQuietly();
        }

        PlatformAdmin::query()->updateOrCreate(
            ['user_id' => $user->getKey()],
            ['is_active' => true],
        );

        $this->command?->info("Super Admin ready: {$email}");
    }
}
