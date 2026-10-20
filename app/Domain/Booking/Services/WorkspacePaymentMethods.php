<?php

namespace App\Domain\Booking\Services;

use App\Domain\Tenant\Models\Tenant;
use App\Support\PlatformSettings;

final class WorkspacePaymentMethods
{
    private const MODES = ['offline_only', 'online_only', 'both'];

    public function __construct(
        private readonly PlatformSettings $platformSettings,
    ) {
    }

    public function mode(Tenant $tenant): string
    {
        $configured = data_get($tenant->profile?->booking_settings, 'payment_method_mode');

        if (is_string($configured) && in_array($configured, self::MODES, true)) {
            return $configured;
        }

        // Preserve prior behavior for existing workspaces until a preference is saved.
        return $this->platformOnlinePaymentsEnabled() ? 'both' : 'offline_only';
    }

    /**
     * @return list<'cash'|'online'>
     */
    public function availableMethods(Tenant $tenant): array
    {
        $methods = match ($this->mode($tenant)) {
            'offline_only' => ['cash'],
            'online_only' => ['online'],
            default => ['cash', 'online'],
        };

        if (!$this->platformOnlinePaymentsEnabled()) {
            $methods = array_values(array_filter(
                $methods,
                static fn (string $method): bool => $method !== 'online',
            ));
        }

        return $methods;
    }

    public function allows(Tenant $tenant, string $method): bool
    {
        return in_array($method, $this->availableMethods($tenant), true);
    }

    public function platformOnlinePaymentsEnabled(): bool
    {
        return filter_var(
            $this->platformSettings->get('centralized_booking_payments_enabled', false),
            FILTER_VALIDATE_BOOL,
        );
    }
}
