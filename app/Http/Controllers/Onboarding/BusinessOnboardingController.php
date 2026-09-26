<?php

namespace App\Http\Controllers\Onboarding;

use App\Domain\Business\Actions\CreateBusiness;
use App\Domain\Business\Models\BusinessType;
use App\Domain\Module\Models\Module;
use App\Domain\Scheduling\Models\BusinessWorkingHour;
use App\Domain\Billing\Models\Subscription;
use App\Domain\Billing\Enums\SubscriptionStatus;
use App\Domain\Tenant\Services\CurrentTenant;
use App\Http\Requests\Onboarding\StoreBusinessRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BusinessOnboardingController
{
    public function create(): View
    {
        return view('onboarding.business.create', [
            'businessTypes' => Cache::remember(
                'bookresa:business-types:active',
                now()->addMinutes(10),
                fn () => BusinessType::query()
                    ->where('is_active', true)
                    ->orderBy('id')
                    ->get(['id', 'slug', 'name']),
            ),
        ]);
    }

    public function store(
        StoreBusinessRequest $request,
        CreateBusiness $createBusiness,
    ): RedirectResponse {
        $businessType = BusinessType::query()
            ->where('is_active', true)
            ->findOrFail($request->integer('business_type_id'));

        $tenant = $createBusiness->handle(
            $request->user(),
            $businessType,
            $request->validated(),
        );

        $request->session()->put('tenant_id', $tenant->getKey());

        return to_route('onboarding.workspace');
    }

    public function workspace(
        Request $request,
        CurrentTenant $currentTenant,
    ): View {
        abort_unless($request->user() !== null, 401);

        $tenant = $currentTenant->get();
        abort_unless($tenant !== null, 404);

        $subscription = Subscription::query()
            ->with('plan.modules')
            ->whereIn('status', [
                SubscriptionStatus::Trial->value,
                SubscriptionStatus::Active->value,
            ])
            ->latest('start_at')
            ->first();

        $entitledModuleKeys = collect(data_get($subscription?->pricing_snapshot, 'modules', []))
            ->pluck('key')
            ->filter()
            ->values();

        if ($subscription?->pricing_snapshot === null && $subscription?->isUsable()) {
            $entitledModuleKeys = $subscription->plan?->modules->pluck('key') ?? collect();
        }

        $hasModules = $tenant->modules()->wherePivot('enabled', true)->exists();
        $hasServices = $tenant->services()->exists();
        $hasHours = BusinessWorkingHour::query()->exists();
        $hasStaff = $tenant->staffProfiles()->exists();
        $isReady = (bool) data_get($tenant->settings, 'onboarding.completed', false);

        return view('onboarding.workspace', [
            'tenant' => $tenant->loadMissing(['profile', 'businessType', 'modules']),
            'modules' => Module::query()
                ->where('is_active', true)
                ->orderByDesc('is_core')
                ->orderBy('id')
                ->get(),
            'entitledModuleKeys' => $entitledModuleKeys,
            'hasSubscription' => $subscription !== null,
            'steps' => [
                ['key' => 'workspace', 'label' => __('Workspace'), 'route' => 'onboarding.workspace', 'complete' => true],
                ['key' => 'modules', 'label' => __('Modules'), 'route' => 'onboarding.workspace', 'complete' => $hasModules],
                ['key' => 'services', 'label' => __('Services'), 'route' => 'services.index', 'complete' => $hasServices],
                ['key' => 'hours', 'label' => __('Working hours'), 'route' => 'scheduling.index', 'complete' => $hasHours],
                ['key' => 'staff', 'label' => __('Staff'), 'route' => 'staff.index', 'complete' => $hasStaff],
                ['key' => 'ready', 'label' => __('Ready'), 'route' => 'dashboard', 'complete' => $isReady],
            ],
        ]);
    }

    public function complete(
        Request $request,
        CurrentTenant $currentTenant,
    ): RedirectResponse {
        $tenant = $currentTenant->get();
        abort_unless($tenant !== null, 404);

        $hasModules = $tenant->modules()->wherePivot('enabled', true)->exists();
        $hasServices = $tenant->services()->exists();
        $hasHours = BusinessWorkingHour::query()->exists();
        $hasStaff = $tenant->staffProfiles()->exists();

        if (! $hasModules || ! $hasServices || ! $hasHours || ! $hasStaff) {
            return back()->withErrors([
                'onboarding' => __('Complete workspace modules, services, working hours and staff before finishing onboarding.'),
            ]);
        }

        $settings = $tenant->settings ?? [];
        data_set($settings, 'onboarding.step', 'ready');
        data_set($settings, 'onboarding.completed', true);
        $tenant->forceFill(['settings' => $settings])->save();

        return to_route('dashboard')->with('status', __('Workspace is ready. Your booking page is now available.'));
    }

    public function updateModules(
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
            ->first();

        $entitledKeys = collect(
            data_get($subscription?->pricing_snapshot, 'modules', [])
        )->pluck('key')->filter()->values();

        if ($subscription?->pricing_snapshot === null && $subscription?->isUsable()) {
            $entitledKeys = $subscription->plan?->modules->pluck('key') ?? collect();
        }

        $selected = collect($validated['module_ids'] ?? [])
            ->map(static fn ($id): int => (int) $id)
            ->flip();

        $coreIds = $modules->where('is_core', true)->pluck('id');
        $selected = $selected->merge($coreIds->mapWithKeys(fn (int $id): array => [$id => true]));

        $unavailable = $modules
            ->where('is_core', false)
            ->filter(fn (Module $module): bool => $selected->has($module->id) && ! $entitledKeys->contains($module->key));

        if ($unavailable->isNotEmpty()) {
            throw ValidationException::withMessages([
                'module_ids' => __('Some selected modules are not included in the current subscription plan.'),
            ]);
        }

        $now = now();
        DB::transaction(function () use ($tenant, $modules, $selected, $now): void {
            $rows = $modules->map(fn (Module $module): array => [
                'tenant_id' => $tenant->getKey(),
                'module_id' => $module->getKey(),
                'enabled' => $selected->has($module->id),
                'created_at' => $now,
                'updated_at' => $now,
            ])->all();

            \App\Domain\Module\Models\TenantModule::withoutGlobalScopes()->upsert(
                $rows,
                ['tenant_id', 'module_id'],
                ['enabled', 'updated_at'],
            );
        });

        $settings = $tenant->settings ?? [];
        data_set($settings, 'onboarding.step', 'services');
        $tenant->forceFill(['settings' => $settings])->save();

        return to_route('services.index')->with('status', __('Workspace modules configured. Next, add your services.'));
    }
}
