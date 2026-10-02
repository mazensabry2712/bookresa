<?php

namespace App\Http\Controllers\Platform;

use App\Domain\Platform\Models\PlatformBroadcast;
use App\Domain\Tenant\Models\Tenant;
use App\Jobs\DeliverPlatformBroadcast;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class PlatformBroadcastController
{
    public function index(): View
    {
        return view('admin.broadcasts.index', [
            'broadcasts' => PlatformBroadcast::query()
                ->with(['creator', 'tenant'])
                ->latest('id')
                ->paginate(20),
            'tenants' => Tenant::query()
                ->with(['profile' => fn ($query) => $query->withoutGlobalScopes()])
                ->where('status', 'active')
                ->orderByDesc('id')
                ->get(),
        ]);
    }

    public function store(Request $request, AuditLogger $audit): RedirectResponse
    {
        $validated = $request->validate([
            'tenant_id' => ['nullable', 'integer', 'exists:tenants,id'],
            'title_en' => ['required', 'string', 'max:180'],
            'title_ar' => ['required', 'string', 'max:180'],
            'message_en' => ['required', 'string', 'max:10000'],
            'message_ar' => ['required', 'string', 'max:10000'],
        ]);

        $broadcast = PlatformBroadcast::query()->create([
            'created_by_user_id' => $request->user()->getKey(),
            'tenant_id' => $validated['tenant_id'] ?? null,
            'title_en' => $validated['title_en'],
            'title_ar' => $validated['title_ar'],
            'message_en' => $validated['message_en'],
            'message_ar' => $validated['message_ar'],
            'status' => 'pending',
        ]);

        DeliverPlatformBroadcast::dispatch($broadcast->getKey());

        $audit->log('platform.broadcast_created', $broadcast, [
            'broadcast_id' => (int) $broadcast->getKey(),
            'tenant_id' => $broadcast->tenant_id,
        ]);

        return back()->with('status', __('Broadcast queued successfully.'));
    }
}
