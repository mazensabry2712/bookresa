<?php

namespace App\Providers;

use App\Domain\Payment\Contracts\PaymentGateway;
use App\Domain\Tenant\Services\CurrentTenant;
use App\Infrastructure\Payments\Kashier\KashierGateway;
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
        //
    }
}
