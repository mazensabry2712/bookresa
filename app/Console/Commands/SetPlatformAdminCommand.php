<?php

namespace App\Console\Commands;

use App\Domain\Platform\Models\PlatformAdmin;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Console\Command;

final class SetPlatformAdminCommand extends Command
{
    protected $signature = 'platform-admin:set
                            {email : Existing user email}
                            {--revoke : Disable platform administrator access}';

    protected $description = 'Grant or revoke platform administrator access for an existing user';

    public function handle(): int
    {
        $email = strtolower(trim((string) $this->argument('email')));
        $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();

        if ($user === null) {
            $this->error('User not found.');

            return self::FAILURE;
        }

        $admin = PlatformAdmin::query()->firstOrNew(['user_id' => $user->getKey()]);
        $admin->is_active = ! $this->option('revoke');
        $admin->save();

        app(AuditLogger::class)->log(
            $admin->is_active ? 'platform_admin.granted' : 'platform_admin.revoked',
            $admin,
            ['user_id' => (int) $user->getKey(), 'is_active' => (bool) $admin->is_active],
        );

        $this->info($admin->is_active
            ? 'Platform administrator access granted.'
            : 'Platform administrator access revoked.');

        return self::SUCCESS;
    }
}
