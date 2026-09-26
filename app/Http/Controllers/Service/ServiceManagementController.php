<?php

namespace App\Http\Controllers\Service;

use App\Domain\Service\Actions\CreateService;
use App\Domain\Service\Actions\DeleteService;
use App\Domain\Service\Actions\UpdateService;
use App\Domain\Service\Models\Service;
use App\Domain\Tenant\Services\CurrentTenant;
use App\Http\Requests\Service\StoreServiceRequest;
use App\Http\Requests\Service\UpdateServiceRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

final class ServiceManagementController
{
    public function index(Request $request, CurrentTenant $currentTenant): View
    {
        abort_unless($currentTenant->get() !== null, 404);

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

    public function store(StoreServiceRequest $request, CreateService $createService): RedirectResponse
    {
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

        return to_route('services.index')->with('status', __('service_ui.created'));
    }

    public function update(
        UpdateServiceRequest $request,
        Service $service,
        UpdateService $updateService,
    ): RedirectResponse {
        try {
            $updateService->handle($service, $request->validated());

            return to_route('services.index')->with('status', __('service_ui.updated'));
        } catch (RuntimeException $exception) {
            return back()->withErrors(['service' => $exception->getMessage()]);
        }
    }

    public function destroy(
        Request $request,
        Service $service,
        DeleteService $deleteService,
    ): RedirectResponse {
        abort_unless($request->user()?->can('services.delete'), 403);

        try {
            $deleteService->handle($service);

            return to_route('services.index')->with('status', __('service_ui.deleted'));
        } catch (RuntimeException $exception) {
            return back()->withErrors(['service' => $exception->getMessage()]);
        }
    }

    private function toMinorUnits(string $amount): int
    {
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');

        return ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
    }
}
