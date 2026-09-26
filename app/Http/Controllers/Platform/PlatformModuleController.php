<?php

namespace App\Http\Controllers\Platform;

use App\Domain\Billing\Enums\SubscriptionStatus;
use App\Domain\Billing\Models\Subscription;
use App\Domain\Module\Models\Module;
use App\Domain\Module\Models\TenantModule;
use App\Domain\Tenant\Models\Tenant;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;

final class PlatformModuleController
{
    public function index(Tenant $tenant): View
    {
        $modules = Module::query()
            ->orderByDesc('is_core')
            ->orderBy('id')
            ->get();

        $tenantModules = TenantModule::withoutGlobalScopes()
            ->where('tenant_id', $tenant->getKey())
            ->pluck('enabled', 'module_id');

        $subscription = Subscription::withoutGlobalScopes()
            ->where('tenant_id', $tenant->getKey())
            ->whereIn('status', [
                SubscriptionStatus::Trial->value,
                SubscriptionStatus::Active->value,
            ])
            ->latest('start_at')
            ->first(fn (Subscription $subscription): bool => $subscription->isUsable());

        $entitledModuleKeys = collect(data_get($subscription?->pricing_snapshot, 'modules', []))
            ->pluck('key')
            ->filter()
            ->values()
            ->all();

        return view('admin.modules.index', [
            'tenant' => $tenant->load([
                'businessType',
                'profile' => fn ($query) => $query->withoutGlobalScopes(),
            ]),
            'modules' => $modules,
            'tenantModules' => $tenantModules,
            'entitledModuleKeys' => $entitledModuleKeys,
            'subscription' => $subscription,
        ]);
    }

    public function update(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'module_ids' => ['nullable', 'array'],
            'module_ids.*' => ['integer', 'distinct', 'exists:modules,id'],
        ]);

        $selectedModuleIds = collect($validated['module_ids'] ?? [])
            ->map(static fn ($id): int => (int) $id)
            ->flip();

        $modules = Module::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get(['id', 'key', 'is_core']);

        $subscription = Subscription::withoutGlobalScopes()
            ->where('tenant_id', $tenant->getKey())
            ->whereIn('status', [
                SubscriptionStatus::Trial->value,
                SubscriptionStatus::Active->value,
            ])
            ->latest('start_at')
            ->first(fn (Subscription $subscription): bool => $subscription->isUsable());

        $entitledModuleKeys = collect(data_get($subscription?->pricing_snapshot, 'modules', []))
            ->pluck('key')
            ->filter()
            ->values()
            ->all();

        $invalidOptionalModules = $modules
            ->filter(fn (Module $module): bool => $selectedModuleIds->has($module->id)
                && ! $module->is_core
                && ! in_array($module->key, $entitledModuleKeys, true))
            ->pluck('key')
            ->values();

        if ($invalidOptionalModules->isNotEmpty()) {
            throw ValidationException::withMessages([
                'module_ids' => __('Selected modules are not included in the current subscription plan.'),
            ]);
        }

        $timestamp = now();

        DB::transaction(function () use ($tenant, $modules, $selectedModuleIds, $timestamp): void {
            $rows = $modules->map(fn (Module $module): array => [
                'tenant_id' => $tenant->getKey(),
                'module_id' => $module->getKey(),
                'enabled' => $selectedModuleIds->has($module->id),
                'updated_at' => $timestamp,
                'created_at' => $timestamp,
            ])->all();

            TenantModule::withoutGlobalScopes()->upsert(
                $rows,
                ['tenant_id', 'module_id'],
                ['enabled', 'updated_at'],
            );
        });

        app(AuditLogger::class)->log(
            'platform.tenant_modules_updated',
            $tenant,
            [
                'tenant_id' => (int) $tenant->getKey(),
                'enabled_module_ids' => $selectedModuleIds->keys()->map(fn ($id): int => (int) $id)->values()->all(),
            ],
        );

        return back()->with('status', __('Workspace modules updated successfully.'));
    }
}
