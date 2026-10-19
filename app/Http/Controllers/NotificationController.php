<?php

namespace App\Http\Controllers;

use App\Domain\Module\Services\TenantModuleAccess;
use App\Domain\Tenant\Models\Tenant;
use App\Domain\Tenant\Services\CurrentTenant;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class NotificationController
{
    public function index(CurrentTenant $currentTenant): View
    {
        $tenant = $currentTenant->get();

        abort_unless($tenant !== null, 404);

        $timezone = (string) ($tenant->profile?->timezone ?: config('app.timezone', 'UTC'));

        try {
            CarbonImmutable::now($timezone);
        } catch (\Throwable) {
            $timezone = (string) (config('app.timezone') ?: 'UTC');
        }

        /** @var User $user */
        $user = request()->user();

        $tenantFilter = $this->tenantFilter($tenant->getKey());

        $notifications = $user->notifications()
            ->whereRaw($tenantFilter[0], $tenantFilter[1])
            ->latest('created_at')
            ->paginate(25)
            ->withQueryString();

        $unreadCount = $user->notifications()
            ->whereNull('read_at')
            ->whereRaw($tenantFilter[0], $tenantFilter[1])
            ->count();

        return view('notifications.index', [
            'tenant' => $tenant,
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
            'timezone' => $timezone,
        ]);
    }

    public function read(
        Request $request,
        CurrentTenant $currentTenant,
        Tenant $tenant,
        string $notification,
    ): RedirectResponse {
        $activeTenant = $currentTenant->get();

        abort_unless($activeTenant !== null && $tenant->is($activeTenant), 404);

        /** @var User $user */
        $user = $request->user();

        $record = $user->notifications()
            ->whereKey($notification)
            ->firstOrFail();

        abort_unless(
            (int) data_get($record->data, 'tenant_id', 0) === (int) $tenant->getKey(),
            404,
        );

        if ($record->read_at === null) {
            $record->markAsRead();
        }

        $type = (string) data_get($record->data, 'type', '');
        $moduleAccess = app(TenantModuleAccess::class);
        $bookingId = data_get($record->data, 'booking_id');

        if ($bookingId !== null && $user->can('bookings.view')) {
            if ($moduleAccess->allows('appointments')) {
                return to_route('booking.management.show', [
                    'tenant' => $tenant->slug,
                    'booking' => $bookingId,
                ]);
            }

            if (
                $user->can('billing.view')
                && $moduleAccess->requiresSubscription('appointments')
                && ! $moduleAccess->hasUsableSubscription()
            ) {
                return to_route('billing.subscription', ['tenant' => $tenant->slug]);
            }
        }

        $ticketId = data_get($record->data, 'ticket_id');

        if ($type === 'support_reply' && $ticketId !== null && $user->can('support.view')) {
            return to_route('support.show', [
                'tenant' => $tenant->slug,
                'ticket' => $ticketId,
            ]);
        }

        if (
            in_array($type, ['payment_paid', 'payment_failed', 'business_payment_paid'], true)
            && $user->can('billing.view')
        ) {
            if ($moduleAccess->allows('payments')) {
                return to_route('payments.index', ['tenant' => $tenant->slug]);
            }

            if (
                $moduleAccess->requiresSubscription('payments')
                && ! $moduleAccess->hasUsableSubscription()
            ) {
                return to_route('billing.subscription', ['tenant' => $tenant->slug]);
            }
        }

        if (
            in_array($type, ['subscription_expiring', 'usage_warning'], true)
            && $user->can('billing.view')
        ) {
            return to_route('billing.subscription', ['tenant' => $tenant->slug]);
        }

        if ($user->can('notifications.view')) {
            return to_route('notifications.index', ['tenant' => $tenant->slug]);
        }

        return back();
    }

    public function readAll(Request $request, CurrentTenant $currentTenant): RedirectResponse
    {
        $tenant = $currentTenant->get();

        abort_unless($tenant !== null, 404);

        /** @var User $user */
        $user = $request->user();

        [$tenantWhere, $tenantBindings] = $this->tenantFilter($tenant->getKey());

        $user->notifications()
            ->whereNull('read_at')
            ->whereRaw($tenantWhere, $tenantBindings)
            ->update(['read_at' => now()]);

        return back()->with('status', __('app.notification_ui.all_marked_read'));
    }

    /** @return array{0:string,1:array<int,int>} */
    private function tenantFilter(int|string $tenantId): array
    {
        return [
            "JSON_EXTRACT(data, '$.tenant_id') = ?",
            [(int) $tenantId],
        ];
    }
}
