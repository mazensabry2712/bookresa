<?php

use App\Console\Commands\ExpireSubscriptionsCommand;
use App\Console\Commands\GenerateUsagePeriodsCommand;
use App\Console\Commands\SendBillingNotifications;
use App\Console\Commands\SendBookingReminders;
use App\Console\Commands\SetPlatformAdminCommand;
use App\Domain\Platform\Models\PlatformAdmin;
use App\Domain\Tenant\Enums\MembershipStatus;
use App\Domain\Tenant\Enums\TenantStatus;
use App\Http\Middleware\EnsurePlatformAdmin;
use App\Http\Middleware\EnsureTenantModuleEnabled;
use App\Http\Middleware\EnsureTenantSubscriptionUsable;
use App\Http\Middleware\ResolveTenant;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Spatie\Permission\Middleware\PermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withCommands([
        ExpireSubscriptionsCommand::class,
        GenerateUsagePeriodsCommand::class,
        SetPlatformAdminCommand::class,
        SendBookingReminders::class,
        SendBillingNotifications::class,
    ])
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('subscriptions:expire')
            ->hourly()
            ->withoutOverlapping();

        $schedule->command('bookresa:generate-usage-periods')
            ->hourlyAt(15)
            ->withoutOverlapping();
        $schedule->command('bookresa:send-booking-reminders')
            ->hourly()
            ->withoutOverlapping();

        $schedule->command('bookresa:send-billing-notifications')
            ->dailyAt('09:00')
            ->withoutOverlapping();
    })
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectUsersTo(function (Request $request): string {
            $user = $request->user();

            if ($user === null) {
                return route('home');
            }

            if ($user->hasVerifiedEmail() === false) {
                return route('verification.notice');
            }

            if (PlatformAdmin::query()
                ->where('user_id', $user->getKey())
                ->where('is_active', true)
                ->exists()) {
                return route('admin.dashboard');
            }

            $membership = $user->tenantMemberships()
                ->where('status', MembershipStatus::Active->value)
                ->whereHas('tenant', fn ($query) => $query->where('status', TenantStatus::Active->value))
                ->with('tenant')
                ->orderByDesc('is_primary')
                ->orderBy('id')
                ->first();

            if ($membership?->tenant !== null) {
                return route('dashboard', ['tenant' => $membership->tenant->slug]);
            }

            return route('onboarding.business.create');
        });
        $middleware->web(append: [
            SecurityHeaders::class,
            SetLocale::class,
        ]);

        $middleware->alias([
            'tenant' => ResolveTenant::class,
            'platform' => EnsurePlatformAdmin::class,
            'permission' => PermissionMiddleware::class,
            'module' => EnsureTenantModuleEnabled::class,
            'subscription.usable' => EnsureTenantSubscriptionUsable::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'webhooks/kashier',
            'payments/kashier/return',
        ]);

        $middleware->prependToPriorityList(
            before: SubstituteBindings::class,
            prepend: ResolveTenant::class,
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
