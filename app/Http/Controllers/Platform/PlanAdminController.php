<?php

namespace App\Http\Controllers\Platform;

use App\Domain\Billing\Enums\PlanBillingPeriod;
use App\Domain\Billing\Enums\SubscriptionStatus;
use App\Domain\Billing\Models\Plan;
use App\Domain\Billing\Services\PlanCatalog;
use App\Domain\Billing\Services\UpsertPlan;
use App\Domain\Module\Models\Module;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class PlanAdminController
{
    public function index(): View
    {
        $plans = Plan::query()
            ->with('modules')
            ->withCount([
                'subscriptions as active_subscriptions_count' => fn ($query) => $query
                    ->withoutGlobalScopes()
                    ->whereIn('status', [
                        SubscriptionStatus::Trial->value,
                        SubscriptionStatus::Active->value,
                    ]),
            ])
            ->orderByDesc('id')
            ->paginate(15);

        return view('admin.plans.index', [
            'plans' => $plans,
            'activePlanCount' => Plan::query()->active()->count(),
        ]);
    }

    public function create(): View
    {
        return view('admin.plans.create', [
            'plan' => null,
            'modules' => Module::query()->where('is_active', true)->orderBy('id')->get(),
        ]);
    }

    public function store(Request $request, UpsertPlan $upsertPlan): RedirectResponse
    {
        $validated = $this->validated($request);
        $plan = $upsertPlan->handle(null, $this->toPlanData($validated), $validated['module_ids'] ?? []);

        app(AuditLogger::class)->log(
            'pricing.plan_created',
            $plan,
            ['plan_id' => (int) $plan->getKey(), 'price_minor' => (int) $plan->price_minor, 'currency' => $plan->currency],
        );

        return to_route('admin.plans.edit', $plan)->with('status', 'Plan created successfully.');
    }

    public function edit(Plan $plan): View
    {
        return view('admin.plans.edit', [
            'plan' => $plan->load('modules'),
            'modules' => Module::query()->where('is_active', true)->orderBy('id')->get(),
        ]);
    }

    public function update(Request $request, Plan $plan, UpsertPlan $upsertPlan): RedirectResponse
    {
        $validated = $this->validated($request);
        $upsertPlan->handle($plan, $this->toPlanData($validated), $validated['module_ids'] ?? []);

        $fresh = $plan->fresh();

        app(AuditLogger::class)->log(
            'pricing.plan_updated',
            $fresh,
            ['plan_id' => (int) $fresh->getKey(), 'price_minor' => (int) $fresh->price_minor, 'currency' => $fresh->currency],
        );

        return to_route('admin.plans.edit', $plan->fresh('modules'))->with('status', 'Plan updated successfully.');
    }

    public function toggle(Plan $plan, PlanCatalog $planCatalog): RedirectResponse
    {
        $plan->forceFill(['is_active' => !$plan->is_active])->save();
        $planCatalog->forget();

        app(AuditLogger::class)->log(
            'pricing.plan_toggled',
            $plan,
            ['plan_id' => (int) $plan->getKey(), 'is_active' => (bool) $plan->is_active],
        );

        return back()->with('status', $plan->is_active
            ? 'Plan activated successfully.'
            : 'Plan deactivated successfully.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name.en' => ['required', 'string', 'max:120'],
            'name.ar' => ['nullable', 'string', 'max:120'],
            'description.en' => ['nullable', 'string', 'max:1000'],
            'description.ar' => ['nullable', 'string', 'max:1000'],
            'price' => ['required', 'regex:/^\d+(?:\.\d{1,2})?$/'],
            'currency' => ['required', 'string', 'size:3'],
            'billing_period' => ['required', Rule::in(array_map(fn (PlanBillingPeriod $period): string => $period->value, PlanBillingPeriod::cases()))],
            'included_customer_limit' => ['required', 'integer', 'min:0'],
            'additional_customer_price' => ['required', 'regex:/^\d+(?:\.\d{1,2})?$/'],
            'trial_days' => ['required', 'integer', 'min:0', 'max:3650'],
            'is_active' => ['nullable', 'boolean'],
            'module_ids' => ['nullable', 'array'],
            'module_ids.*' => ['integer', 'exists:modules,id'],
        ]);
    }

    /** @param array<string, mixed> $validated */
    private function toPlanData(array $validated): array
    {
        return [
            'name' => [
                'en' => trim((string) data_get($validated, 'name.en')),
                'ar' => trim((string) data_get($validated, 'name.ar', '')),
            ],
            'description' => [
                'en' => trim((string) data_get($validated, 'description.en', '')),
                'ar' => trim((string) data_get($validated, 'description.ar', '')),
            ],
            'price_minor' => $this->moneyToMinor((string) $validated['price']),
            'currency' => strtoupper(trim((string) $validated['currency'])),
            'billing_period' => $validated['billing_period'],
            'included_customer_limit' => (int) $validated['included_customer_limit'],
            'additional_customer_price_minor' => $this->moneyToMinor((string) $validated['additional_customer_price']),
            'trial_days' => (int) $validated['trial_days'],
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ];
    }

    private function moneyToMinor(string $value): int
    {
        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '0');

        return ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
    }
}
