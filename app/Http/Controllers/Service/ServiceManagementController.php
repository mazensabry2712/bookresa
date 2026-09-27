<?php

namespace App\Http\Controllers\Service;

use App\Domain\Service\Actions\CreateService;
use App\Domain\Service\Actions\DeleteService;
use App\Domain\Service\Actions\UpdateService;
use App\Domain\Service\Models\Service;
use App\Domain\Tenant\Models\Tenant;
use App\Domain\Tenant\Services\CurrentTenant;
use App\Http\Requests\Service\StoreServiceRequest;
use App\Http\Requests\Service\UpdateServiceRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

final class ServiceManagementController
{
    public function index(Request $request, CurrentTenant $currentTenant): RedirectResponse|View
    {
        $tenant = $currentTenant->get();

        abort_unless($tenant !== null, 404);

        if ($redirect = $this->onboardingRedirect($tenant)) {
            return $redirect;
        }

        $editingService = null;

        if ($request->filled('edit')) {
            $editingService = Service::query()->findOrFail($request->integer('edit'));
        }

        return view('services.index', [
            'tenant' => $currentTenant->get(),
            'services' => Service::query()->latest('id')->paginate(20)->withQueryString(),
            'editingService' => $editingService,
        ]);
    }

    public function store(
        StoreServiceRequest $request,
        CreateService $createService,
        CurrentTenant $currentTenant,
    ): RedirectResponse {
        $tenant = $currentTenant->get();

        abort_unless($tenant !== null, 404);

        if ($redirect = $this->onboardingRedirect($tenant)) {
            return $redirect;
        }

        $data = $request->validated();
        $createService->handle([
            'name' => [
                'en' => $data['name_en'],
                'ar' => $data['name_ar'] ?: $data['name_en'],
            ],
            'description' => [
                'en' => $data['description_en'] ?: null,
                'ar' => $data['description_ar'] ?: null,
            ],
            'price_minor' => $this->toMinorUnits($data['price']),
            'currency' => $data['currency'],
            'duration_minutes' => (int) $data['duration_minutes'],
            'buffer_minutes' => (int) $data['buffer_minutes'],
            'is_active' => (bool) ($data['is_active'] ?? false),
        ]);

        $tenant = $currentTenant->get();

        if ($tenant !== null && ! (bool) data_get($tenant->settings, 'onboarding.completed', false)) {
            $settings = $tenant->settings ?? [];
            data_set($settings, 'onboarding.step', 'hours');
            $tenant->forceFill(['settings' => $settings])->save();

            return to_route('scheduling.index')->with('status', __('app.service_ui.created'));
        }

        return to_route('services.index')->with('status', __('app.service_ui.created'));
    }

    public function update(
        UpdateServiceRequest $request,
        CurrentTenant $currentTenant,
        Service $service,
        UpdateService $updateService,
    ): RedirectResponse {
        $tenant = $currentTenant->get();

        abort_unless($tenant !== null, 404);

        if ($redirect = $this->onboardingRedirect($tenant)) {
            return $redirect;
        }

        try {
            $updateService->handle($service, $request->validated());

            return to_route('services.index')->with('status', __('app.service_ui.updated'));
        } catch (RuntimeException $exception) {
            return back()->withErrors(['service' => $exception->getMessage()]);
        }
    }

    public function destroy(
        Request $request,
        Service $service,
        DeleteService $deleteService,
        CurrentTenant $currentTenant,
    ): RedirectResponse {
        abort_unless($request->user()?->can('services.delete'), 403);

        $tenant = $currentTenant->get();

        abort_unless($tenant !== null, 404);

        if ($redirect = $this->onboardingRedirect($tenant)) {
            return $redirect;
        }

        try {
            $deleteService->handle($service);

            return to_route('services.index')->with('status', __('app.service_ui.deleted'));
        } catch (RuntimeException $exception) {
            return back()->withErrors(['service' => $exception->getMessage()]);
        }
    }

    private function onboardingRedirect(Tenant $tenant): ?RedirectResponse
    {
        if ((bool) data_get($tenant->settings, 'onboarding.completed', false)) {
            return null;
        }

        $step = (string) data_get($tenant->settings, 'onboarding.step', 'workspace');

        return in_array($step, ['services', 'hours', 'staff', 'ready'], true)
            ? null
            : to_route('onboarding.workspace');
    }

    private function toMinorUnits(string $amount): int
    {
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');

        return ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
    }
}
