<?php

namespace App\Http\Controllers\Customer;

use App\Domain\Booking\Enums\BookingStatus;
use App\Domain\Booking\Models\Booking;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Payment\Models\Payment;
use App\Domain\Billing\Enums\SubscriptionStatus;
use App\Domain\Billing\Models\Subscription;
use App\Domain\Billing\Services\CalculateSubscriptionUsage;
use App\Domain\Customer\Actions\CreateCustomer;
use App\Domain\Customer\Actions\UpdateCustomer;
use App\Domain\Customer\Models\Customer;
use App\Domain\Customer\Services\CustomerIdentity;
use App\Domain\Tenant\Services\CurrentTenant;
use App\Http\Requests\Customer\StoreCustomerRequest;
use App\Http\Requests\Customer\UpdateCustomerRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

final class CustomerManagementController
{
    public function index(
        Request $request,
        CurrentTenant $currentTenant,
        CustomerIdentity $identity,
    ): View {
        abort_unless($currentTenant->get() !== null, 404);

        $search = trim((string) $request->input('search'));

        $customers = Customer::query()
            ->withCount('bookings')
            ->when($search !== '', function ($query) use ($search, $identity): void {
                $normalized = $identity->normalizePhone($search);

                $query->where(function ($query) use ($search, $normalized): void {
                    $like = '%'.$search.'%';

                    $query
                        ->where('name', 'like', $like)
                        ->orWhere('phone', 'like', $like)
                        ->orWhere('email', 'like', $like);

                    if ($normalized !== null) {
                        $query->orWhere('normalized_phone', $normalized);
                    }
                });
            })
            ->orderByDesc('last_seen_at')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('customers.index', [
            'tenant' => $currentTenant->get(),
            'customers' => $customers,
            'search' => $search,
        ]);
    }

    public function store(
        StoreCustomerRequest $request,
        CreateCustomer $createCustomer,
    ): RedirectResponse {
        try {
            $createCustomer->handle($request->validated());

            return to_route('customers.index')->with('status', __('app.customer_ui.created'));
        } catch (RuntimeException $exception) {
            return back()->withErrors(['customer' => $exception->getMessage()])->withInput();
        }
    }

    public function show(Customer $customer, CurrentTenant $currentTenant): View
    {
        abort_unless($currentTenant->get() !== null, 404);

        $customer->loadCount('bookings');

        $customerBookingIds = $customer->bookings()->select('id');

        $metrics = [
            'totalBookings' => (int) $customer->bookings()->count(),
            'completedBookings' => (int) $customer->bookings()->where('status', BookingStatus::Completed)->count(),
            'cancelledBookings' => (int) $customer->bookings()->where('status', BookingStatus::Cancelled)->count(),
            'noShows' => (int) $customer->bookings()->where('status', BookingStatus::NoShow)->count(),
            'totalSpentMinor' => (int) Payment::query()
                ->where('payable_type', (new Booking)->getMorphClass())
                ->whereIn('payable_id', $customerBookingIds)
                ->where('status', PaymentStatus::Paid)
                ->sum('amount_minor'),
        ];

        $subscription = Subscription::query()
            ->whereIn('status', [SubscriptionStatus::Trial->value, SubscriptionStatus::Active->value])
            ->latest('start_at')
            ->first();

        $usageSummary = $subscription ? $usageCalculator->handle($subscription) : null;
        $usagePercent = $usageSummary !== null && $usageSummary->includedCustomerLimit > 0
            ? min(100, (int) round(($usageSummary->uniqueCustomerCount / $usageSummary->includedCustomerLimit) * 100))
            : ($usageSummary?->uniqueCustomerCount > 0 ? 100 : 0);

        $upcomingBooking = $customer->bookings()
            ->with(['service', 'staff'])
            ->where('starts_at', '>=', now('UTC'))
            ->whereIn('status', [BookingStatus::Pending, BookingStatus::Confirmed, BookingStatus::Rescheduled])
            ->orderBy('starts_at')
            ->first();

        $paymentHistory = null;

        if ($request->user()?->can('billing.view')) {
            $paymentHistory = Payment::query()
                ->where('payable_type', (new Booking)->getMorphClass())
                ->whereIn('payable_id', $customerBookingIds)
                ->whereIn('status', [PaymentStatus::Paid->value, PaymentStatus::Refunded->value])
                ->orderByDesc('created_at')
                ->paginate(10, ['*'], 'payments_page')
                ->withQueryString();
        }

        return view('customers.show', [
            'tenant' => $currentTenant->get(),
            'customer' => $customer,
            'metrics' => $metrics,
            'upcomingBooking' => $upcomingBooking,
            'usageSummary' => $usageSummary,
            'usagePercent' => $usagePercent,
            'paymentHistory' => $paymentHistory,
            'bookings' => $customer->bookings()
                ->with(['service', 'staff'])
                ->latest('starts_at')
                ->paginate(20)
                ->withQueryString(),
        ]);
    }

    public function update(
        UpdateCustomerRequest $request,
        Customer $customer,
        UpdateCustomer $updateCustomer,
    ): RedirectResponse {
        try {
            $updateCustomer->handle($customer, $request->validated());

            return to_route('customers.show', $customer)->with('status', __('app.customer_ui.updated'));
        } catch (RuntimeException $exception) {
            return back()->withErrors(['customer' => $exception->getMessage()])->withInput();
        }
    }
}
