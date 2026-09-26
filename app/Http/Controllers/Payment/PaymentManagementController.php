<?php

namespace App\Http\Controllers\Payment;

use App\Domain\Booking\Models\Booking;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Payment\Models\Payment;
use App\Domain\Tenant\Services\CurrentTenant;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class PaymentManagementController
{
    public function index(Request $request, CurrentTenant $currentTenant): View
    {
        $tenant = $currentTenant->get();
        abort_unless($tenant !== null, 404);

        $status = $request->string('status')->toString();
        $payments = Payment::query()
            ->where('payable_type', (new Booking)->getMorphClass())
            ->with(['payable.customer', 'payable.service', 'payable.staff'])
            ->when($status !== '' && PaymentStatus::tryFrom($status) !== null, fn ($query) => $query->where('status', $status))
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('payments.index', [
            'tenant' => $tenant,
            'payments' => $payments,
            'statuses' => PaymentStatus::cases(),
        ]);
    }
}
