<?php

namespace App\Http\Controllers\Business;

use App\Domain\Billing\Enums\SubscriptionStatus;
use App\Domain\Billing\Models\Subscription;
use App\Domain\Module\Models\Module;
use App\Domain\Module\Models\TenantModule;
use App\Domain\Tenant\Services\CurrentTenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class BusinessModuleController
{
    public function index(CurrentTenant $currentTenant): View
    {
        $tenant = $currentTenant->get();
        abort_unless($tenant !== null, 404);

        $subscription = Subscription::query()
            ->with('plan.modules')
            ->whereIn('status', [
                SubscriptionStatus::Trial->value,
                SubscriptionStatus::Active->value,
            ])
            ->latest('start_at')
            ->get()
            ->first(fn (Subscription $subscription): bool => $subscription->isUsable());

        $entitledModuleKeys = collect(data_get($subscription?->pricing_snapshot, 'modules', []))
            ->pluck('key')
            ->filter()
            ->values();

        if ($subscription?->pricing_snapshot === null && $subscription?->isUsable()) {
            $entitledModuleKeys = $subscription->plan?->modules->pluck('key') ?? collect();
        }

        return view('business.modules.index', [
            'tenant' => $tenant->loadMissing(['profile', 'modules']),
            'modules' => Module::query()
                ->where('is_active', true)
                ->orderByDesc('is_core')
                ->orderBy('id')
                ->get(),
            'entitledModuleKeys' => $entitledModuleKeys,
            'hasSubscription' => $subscription !== null,
        ]);
    }

    public function update(
        Request $request,
        CurrentTenant $currentTenant,
    ): RedirectResponse {
        $tenant = $currentTenant->get();
        abort_unless($tenant !== null, 404);

        $validated = $request->validate([
            'module_ids' => ['nullable', 'array'],
            'module_ids.*' => ['integer', 'distinct', 'exists:modules,id'],
        ]);

        $modules = Module::query()
            ->where('is_active', true)
            ->get(['id', 'key', 'is_core']);

        $subscription = Subscription::query()
            ->with('plan.modules')
            ->whereIn('status', [
                SubscriptionStatus::Trial->value,
                SubscriptionStatus::Active->value,
            ])
            ->latest('start_at')
            ->get()
            ->first(fn (Subscription $subscription): bool => $subscription->isUsable());

        $entitledKeys = collect(
            data_get($subscription?->pricing_snapshot, 'modules', [])
        )->pluck('key')->filter()->values();

        if ($subscription?->pricing_snapshot === null && $subscription?->isUsable()) {
            $entitledKeys = $subscription->plan?->modules->pluck('key') ?? collect();
        }

        $selectedIds = collect($validated['module_ids'] ?? [])
            ->map(static fn ($id): int => (int) $id)
            ->merge(
                $modules->where('is_core', true)->pluck('id')
                    ->map(static fn ($id): int => (int) $id),
            )
            ->unique()
            ->values();

        $unavailable = $modules
            ->where('is_core', false)
            ->filter(fn (Module $module): bool => $selectedIds->contains($module->id) && ! $entitledKeys->contains($module->key));

        if ($unavailable->isNotEmpty()) {
            throw ValidationException::withMessages([
                'module_ids' => __('Some selected modules are not included in the current subscription plan.'),
            ]);
        }

        $now = now();

        DB::transaction(function () use ($tenant, $modules, $selectedIds, $now): void {
            $rows = $modules->map(fn (Module $module): array => [
                'tenant_id' => $tenant->getKey(),
                'module_id' => $module->getKey(),
                'enabled' => $selectedIds->contains($module->id),
                'created_at' => $now,
                'updated_at' => $now,
            ])->all();

            TenantModule::withoutGlobalScopes()->upsert(
                $rows,
                ['tenant_id', 'module_id'],
                ['enabled', 'updated_at'],
            );
        });

        return back()->with('status', __('app.module_ui.updated'));
    }
}
