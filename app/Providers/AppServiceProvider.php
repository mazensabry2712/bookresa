<?php

namespace App\Providers;

use App\Domain\Payment\Contracts\PaymentGateway;
use App\Domain\Tenant\Services\CurrentTenant;
use App\Models\User;
use App\Infrastructure\Payments\Kashier\KashierGateway;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CurrentTenant::class);

        $this->app->bind(PaymentGateway::class, function (): PaymentGateway {
            return match ((string) config('bookresa.payments.default_provider', 'kashier')) {
                'kashier' => app(KashierGateway::class),
                default => throw new RuntimeException('Unsupported payment provider.'),
            };
        });
    }

    public function boot(): void
    {
        Event::listen(Login::class, function (Login $event): void {
            app(\App\Support\AuditLogger::class)->log(
                'auth.login',
                $event->user,
                ['user_id' => (int) $event->user->getKey(), 'remember' => (bool) $event->remember],
                $event->user,
            );
        });

        Event::listen(Logout::class, function (Logout $event): void {
            app(\App\Support\AuditLogger::class)->log(
                'auth.logout',
                $event->user,
                ['user_id' => (int) $event->user->getKey()],
                $event->user,
            );
        });

        Event::listen(Failed::class, function (Failed $event): void {
            app(\App\Support\AuditLogger::class)->log(
                'auth.login_failed',
                $event->user,
                [
                    'email' => (string) ($event->credentials['email'] ?? ''),
                    'guard' => $event->guard,
                ],
                $event->user,
            );
        });

        Gate::before(function ($user): ?bool {
            if (! $user instanceof User) {
                return null;
            }

            $platformAdmin = $user->relationLoaded('platformAdmin')
                ? $user->platformAdmin
                : $user->load('platformAdmin')->platformAdmin;

            return $platformAdmin?->is_active === true ? true : null;
        });

        VerifyEmail::toMailUsing(function (object $notifiable, string $url): MailMessage {
            return (new MailMessage)
                ->subject(__('app.verify_email_subject'))
                ->view(['emails.auth.verify-email', 'emails.auth.verify-email-text'], [
                    'user' => $notifiable,
                    'url' => $url,
                ]);
        });
    }
}
