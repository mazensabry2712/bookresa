<?php

namespace App\Http\Controllers;

use App\Domain\Tenant\Services\CurrentTenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;

final class NotificationController
{
    public function index(CurrentTenant $currentTenant): View
    {
        $tenant = $currentTenant->get();

        abort_unless($tenant !== null, 404);

        /** @var User $user */
        $user = request()->user();

        $notifications = $user->notifications()
            ->latest('created_at')
            ->limit(100)
            ->get()
            ->filter(function (DatabaseNotification $notification) use ($tenant): bool {
                return (int) data_get($notification->data, 'tenant_id', 0) === (int) $tenant->getKey();
            })
            ->values();

        return view('notifications.index', [
            'tenant' => $tenant,
            'notifications' => $notifications,
            'unreadCount' => $notifications->whereNull('read_at')->count(),
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
            return to_route('booking.management.show', ['booking' => $target]);
        }

        if (
            in_array(data_get($record->data, 'type'), ['subscription_expiring', 'usage_warning'], true)
            && $user->can('billing.view')
        ) {
            return to_route('billing.subscription');
        }

        if ($user->can('notifications.view')) {
            return to_route('notifications.index');
        }

        return back();
    }

    public function readAll(Request $request, CurrentTenant $currentTenant): RedirectResponse
    {
        $tenant = $currentTenant->get();

        abort_unless($tenant !== null, 404);

        /** @var User $user */
        $user = $request->user();

        $notifications = $user->notifications()
            ->whereNull('read_at')
            ->latest('created_at')
            ->limit(100)
            ->get()
            ->filter(function (DatabaseNotification $notification) use ($tenant): bool {
                return (int) data_get($notification->data, 'tenant_id', 0) === (int) $tenant->getKey();
            });

        foreach ($notifications as $notification) {
            $notification->markAsRead();
        }

        return back()->with('status', __('app.notification_ui.all_marked_read'));
    }
}
