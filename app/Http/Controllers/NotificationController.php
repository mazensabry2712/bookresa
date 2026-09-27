<?php

namespace App\Http\Controllers;

use App\Domain\Tenant\Services\CurrentTenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class NotificationController
{
    public function index(CurrentTenant $currentTenant): View
    {
        $tenant = $currentTenant->get();

        abort_unless($tenant !== null, 404);

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
        ]);
    }

    public function read(
        Request $request,
        CurrentTenant $currentTenant,
        string $notification,
    ): RedirectResponse {
        $tenant = $currentTenant->get();

        abort_unless($tenant !== null, 404);

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

        $target = data_get($record->data, 'booking_id');

        if ($target !== null && $user->can('bookings.view')) {
            return to_route('booking.management.show', ['tenant' => $tenant->slug, 'booking' => $target]);
        }

        if (
            in_array(data_get($record->data, 'type'), ['subscription_expiring', 'usage_warning'], true)
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
            "JSON_UNQUOTE(JSON_EXTRACT(data, '$.tenant_id')) = ?",
            [(int) $tenantId],
        ];
    }
}
