<?php

namespace App\Http\Controllers\Onboarding;

use App\Domain\Billing\Enums\SubscriptionStatus;
use App\Domain\Billing\Models\Subscription;
use App\Domain\Business\Actions\CreateBusiness;
use App\Domain\Business\Models\BusinessType;
use App\Domain\Module\Models\Module;
use App\Domain\Module\Models\TenantModule;
use App\Domain\Scheduling\Models\BusinessWorkingHour;
use App\Domain\Tenant\Models\Tenant;
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
                'bookresa:business-types:active:v2',
                now()->addMinutes(10),
                fn (): array => BusinessType::query()
                    ->where('is_active', true)
                    ->orderBy('id')
                    ->get(['id', 'slug', 'name'])
                    ->map(static fn (BusinessType $type): array => [
                        'id' => $type->getKey(),
                        'slug' => $type->slug,
                        'name' => $type->name,
                    ])
                    ->all(),
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

        $hasModules = $this->coreModulesReady($tenant);
        $hasServices = $tenant->services()->exists();
        $hasHours = BusinessWorkingHour::query()->exists();
        $hasStaff = $tenant->staffProfiles()->exists();
        $isReady = (bool) data_get($tenant->settings, 'onboarding.completed', false);

        $steps = [
            ['key' => 'workspace', 'label' => __('app.onboarding_steps.workspace'), 'route' => 'onboarding.workspace', 'complete' => true],
            ['key' => 'modules', 'label' => __('app.onboarding_steps.modules'), 'route' => 'onboarding.workspace', 'complete' => $hasModules],
            ['key' => 'services', 'label' => __('app.onboarding_steps.services'), 'route' => 'services.index', 'complete' => $hasServices],
            ['key' => 'hours', 'label' => __('app.onboarding_steps.hours'), 'route' => 'scheduling.index', 'complete' => $hasHours],
            ['key' => 'staff', 'label' => __('app.onboarding_steps.staff'), 'route' => 'staff.index', 'complete' => $hasStaff],
            ['key' => 'ready', 'label' => __('app.onboarding_steps.ready'), 'route' => 'dashboard', 'complete' => $isReady],
        ];

        $currentStepIndex = collect($steps)->search(
            static fn (array $step): bool => ! $step['complete'],
        );

        if ($currentStepIndex === false) {
            $currentStepIndex = count($steps) - 1;
        }

        return view('onboarding.workspace', [
            'tenant' => $tenant->loadMissing(['profile', 'businessType', 'modules']),
            'modules' => Module::query()
                ->where('is_active', true)
                ->orderByDesc('is_core')
                ->orderBy('id')
                ->get(),
            'entitledModuleKeys' => $entitledModuleKeys,
            'hasSubscription' => $subscription !== null,
            'steps' => $steps,
            'currentStepIndex' => $currentStepIndex,
        ]);
    }

    public function complete(
        Request $request,
        CurrentTenant $currentTenant,
    ): RedirectResponse {
        $tenant = $currentTenant->get();
        abort_unless($tenant !== null, 404);

        $hasModules = $this->coreModulesReady($tenant);
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

    private function coreModulesReady(Tenant $tenant): bool
    {
        $coreModuleKeys = collect(config('bookresa.modules.core', []))
            ->filter()
            ->values();

        if ($coreModuleKeys->isEmpty()) {
            return false;
        }

        return $tenant->modules()
            ->wherePivot('enabled', true)
            ->whereIn('key', $coreModuleKeys)
            ->count() === $coreModuleKeys->count();
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

        $settings = $tenant->settings ?? [];
        data_set($settings, 'onboarding.step', 'services');
        $tenant->forceFill(['settings' => $settings])->save();

        return to_route('services.index')->with('status', __('Workspace modules configured. Next, add your services.'));
    }
}
