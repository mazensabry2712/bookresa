<?php

namespace App\Http\Controllers\Business;

use App\Domain\Business\Actions\UpdateBusinessProfile;
use App\Domain\Business\Models\BusinessProfile;
use App\Domain\Tenant\Services\CurrentTenant;
use App\Http\Requests\Business\UpdateBusinessProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class BusinessProfileController
{
    public function edit(CurrentTenant $currentTenant): View
    {
        abort_unless($currentTenant->get() !== null, 404);

        $profile = BusinessProfile::query()->firstOrFail();

        $tenant = $currentTenant->get()->loadMissing(['businessType', 'modules', 'paymentAccount']);

        return view('business.profile', [
            'tenant' => $tenant,
            'profile' => $profile,
            'paymentAccount' => $tenant->paymentAccount,
        ]);
    }

    public function update(
        UpdateBusinessProfileRequest $request,
        UpdateBusinessProfile $updateBusinessProfile,
    ): RedirectResponse {
        $updateBusinessProfile->handle($request->validated());

        return back()->with('status', __('app.business_ui.profile_updated'));
    }
}
