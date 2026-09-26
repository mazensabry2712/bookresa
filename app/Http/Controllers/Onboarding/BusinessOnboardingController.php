<?php

namespace App\Http\Controllers\Onboarding;

use App\Domain\Business\Actions\CreateBusiness;
use App\Domain\Business\Models\BusinessType;
use App\Domain\Tenant\Services\CurrentTenant;
use App\Http\Requests\Onboarding\StoreBusinessRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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

        return view('onboarding.workspace', [
            'tenant' => $currentTenant->get(),
        ]);
    }
}
