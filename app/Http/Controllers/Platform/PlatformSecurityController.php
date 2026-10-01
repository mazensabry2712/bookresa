<?php

namespace App\Http\Controllers\Platform;

use App\Domain\Platform\Models\PlatformAdmin;
use App\Models\User;
use Illuminate\View\View;
use Spatie\Activitylog\Models\Activity;

final class PlatformSecurityController
{
    public function index(): View
    {
        /** @var User $user */
        $user = auth()->user();
        $user->loadMissing('platformAdmin');

        $platformAdmins = PlatformAdmin::query()
            ->with('user:id,name,email')
            ->orderByDesc('is_active')
            ->orderBy('id')
            ->get();

        $recentSecurityEvents = Activity::query()
            ->where('log_name', 'security')
            ->latest('id')
            ->limit(20)
            ->get();

        return view('admin.security.index', [
            'user' => $user,
            'platformAdmins' => $platformAdmins,
            'recentSecurityEvents' => $recentSecurityEvents,
            'twoFactorConfigured' => filled($user->two_factor_secret) && filled($user->two_factor_confirmed_at),
        ]);
    }
}
