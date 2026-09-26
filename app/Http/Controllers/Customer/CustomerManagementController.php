<?php

namespace App\Http\Controllers\Customer;

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

            return to_route('customers.index')->with('status', __('Customer created successfully.'));
        } catch (RuntimeException $exception) {
            return back()->withErrors(['customer' => $exception->getMessage()])->withInput();
        }
    }

    public function show(Customer $customer, CurrentTenant $currentTenant): View
    {
        abort_unless($currentTenant->get() !== null, 404);

        $customer->loadCount('bookings');

        return view('customers.show', [
            'tenant' => $currentTenant->get(),
            'customer' => $customer,
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

            return to_route('customers.show', $customer)->with('status', __('Customer updated successfully.'));
        } catch (RuntimeException $exception) {
            return back()->withErrors(['customer' => $exception->getMessage()])->withInput();
        }
    }
}
