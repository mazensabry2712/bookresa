<?php

namespace App\Http\Controllers\Platform;

use App\Domain\Booking\Enums\BookingStatus;
use App\Domain\Booking\Models\Booking;
use App\Domain\Customer\Models\Customer;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Payment\Models\Payment;
use App\Domain\Tenant\Models\Tenant;
use App\Domain\Tenant\Services\CurrentTenant;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class PlatformCustomerController
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search'));
        $tenantId = $request->integer('tenant_id');
        $currency = strtoupper(trim((string) $request->input('currency')));
        $sort = (string) $request->input('sort', 'spending_desc');
        $vipOnly = $request->boolean('vip');

        $currencies = Payment::withoutGlobalScopes()
            ->where('status', PaymentStatus::Paid->value)
            ->distinct()
            ->orderBy('currency')
            ->pluck('currency');

        if ($currency === '' && $currencies->count() === 1) {
            $currency = (string) $currencies->first();
        }

        if ($currency === '' && str_starts_with($sort, 'spending_')) {
            $sort = 'bookings_desc';
        }

        $paidPayments = Payment::withoutGlobalScopes()
            ->from('payments')
            ->join('bookings', function ($join): void {
                $join->on('bookings.id', '=', 'payments.payable_id')
                    ->where('payments.payable_type', '=', Booking::class);
            })
            ->whereColumn('bookings.customer_id', 'customers.id')
            ->whereColumn('bookings.tenant_id', 'customers.tenant_id')
            ->where('payments.status', PaymentStatus::Paid->value)
            ->when($currency !== '', fn ($query) => $query->where('payments.currency', $currency))
            ->when($currency === '', fn ($query) => $query->whereRaw('1 = 0'));

        $bookings = Booking::withoutGlobalScopes()
            ->whereColumn('bookings.customer_id', 'customers.id')
            ->whereColumn('bookings.tenant_id', 'customers.tenant_id');

        $customers = Customer::withoutGlobalScopes()
            ->select('customers.*')
            ->selectSub(
                (clone $paidPayments)->selectRaw('COALESCE(SUM(payments.amount_minor), 0)'),
                'total_paid_minor',
            )
            ->selectSub(
                (clone $bookings)->selectRaw('COUNT(*)'),
                'bookings_count',
            )
            ->selectSub(
                (clone $paidPayments)->selectRaw('COUNT(DISTINCT bookings.id)'),
                'paid_bookings_count',
            )
            ->selectSub(
                (clone $paidPayments)->selectRaw('MAX(payments.paid_at)'),
                'last_paid_at',
            )
            ->with([
                'tenant' => function ($query): void {
                    $query->withoutGlobalScopes()->with([
                        'profile' => fn ($profile) => $profile->withoutGlobalScopes(),
                        'businessType',
                    ]);
                },
            ])
            ->when($tenantId > 0, fn ($query) => $query->where('customers.tenant_id', $tenantId))
            ->when($vipOnly, fn ($query) => $query->where('customers.is_vip', true))
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($nested) use ($search): void {
                    $nested
                        ->where('customers.name', 'like', '%'.$search.'%')
                        ->orWhere('customers.phone', 'like', '%'.$search.'%')
                        ->orWhere('customers.email', 'like', '%'.$search.'%')
                        ->orWhere('customers.normalized_phone', 'like', '%'.$search.'%');
                });
            });

        $sorts = [
            'spending_desc' => ['total_paid_minor', 'desc'],
            'spending_asc' => ['total_paid_minor', 'asc'],
            'bookings_desc' => ['bookings_count', 'desc'],
            'recent_desc' => ['last_paid_at', 'desc'],
            'newest_desc' => ['customers.id', 'desc'],
            'oldest_asc' => ['customers.id', 'asc'],
        ];

        [$sortColumn, $sortDirection] = $sorts[$sort] ?? $sorts['spending_desc'];

        $customers = $customers
            ->orderBy($sortColumn, $sortDirection)
            ->orderByDesc('customers.id')
            ->paginate(25)
            ->withQueryString();

        $tenants = Tenant::query()
            ->with(['profile' => fn ($query) => $query->withoutGlobalScopes()])
            ->orderByDesc('id')
            ->get();

        return view('admin.customers.index', [
            'customers' => $customers,
            'tenants' => $tenants,
            'sort' => $sort,
            'currency' => $currency,
            'currencies' => $currencies,
            'vipOnly' => $vipOnly,
        ]);
    }

    public function show(Tenant $tenant, int $customer): View
    {
        $tenantId = (int) $tenant->getKey();

        $customer = Customer::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->findOrFail($customer);

        $customer->loadMissing([
            'tenant' => fn ($query) => $query->withoutGlobalScopes()->with([
                'profile' => fn ($profile) => $profile->withoutGlobalScopes(),
                'businessType',
            ]),
        ]);

        $bookingBase = Booking::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('customer_id', $customer->getKey());

        $bookings = (clone $bookingBase)
            ->with([
                'service' => fn ($query) => $query->withoutGlobalScopes(),
                'staff' => fn ($query) => $query->withoutGlobalScopes(),
            ])
            ->latest('starts_at')
            ->limit(50)
            ->get();

        $paymentBase = Payment::withoutGlobalScopes()
            ->where('payments.tenant_id', $tenantId)
            ->where('payments.payable_type', Booking::class)
            ->whereExists(function ($query) use ($tenantId, $customer): void {
                $query->selectRaw('1')
                    ->from('bookings')
                    ->whereColumn('bookings.id', 'payments.payable_id')
                    ->where('bookings.tenant_id', $tenantId)
                    ->where('bookings.customer_id', $customer->getKey());
            });

        $payments = (clone $paymentBase)
            ->orderByDesc('paid_at')
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        $paymentAggregates = (clone $paymentBase)
            ->selectRaw('currency,
                SUM(CASE WHEN status = ? THEN amount_minor ELSE 0 END) AS paid_minor,
                SUM(CASE WHEN status = ? THEN amount_minor ELSE 0 END) AS refunded_minor,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS paid_count,
                MAX(CASE WHEN status = ? THEN paid_at ELSE NULL END) AS last_paid_at', [
                PaymentStatus::Paid->value,
                PaymentStatus::Refunded->value,
                PaymentStatus::Paid->value,
                PaymentStatus::Paid->value,
            ])
            ->groupBy('currency')
            ->get();

        $currencyTotals = $paymentAggregates
            ->mapWithKeys(fn ($row): array => [(string) $row->currency => (int) $row->getAttribute('paid_minor')])
            ->sortKeys()
            ->all();

        $singleCurrencyAggregate = $paymentAggregates->count() === 1
            ? $paymentAggregates->first()
            : null;

        $metrics = [
            'bookings' => (int) (clone $bookingBase)->count(),
            'completedBookings' => (int) (clone $bookingBase)->where('status', BookingStatus::Completed->value)->count(),
            'cancelledBookings' => (int) (clone $bookingBase)->where('status', BookingStatus::Cancelled->value)->count(),
            'noShows' => (int) (clone $bookingBase)->where('status', BookingStatus::NoShow->value)->count(),
            'totalSpentMinor' => (int) ($singleCurrencyAggregate?->getAttribute('paid_minor') ?? 0),
            'refundedMinor' => (int) ($singleCurrencyAggregate?->getAttribute('refunded_minor') ?? 0),
            'averagePaidMinor' => $singleCurrencyAggregate !== null && (int) $singleCurrencyAggregate->getAttribute('paid_count') > 0
                ? (int) round((int) $singleCurrencyAggregate->getAttribute('paid_minor') / (int) $singleCurrencyAggregate->getAttribute('paid_count'))
                : 0,
            'firstSeenAt' => $customer->first_seen_at,
            'lastSeenAt' => $customer->last_seen_at,
            'lastPaidAt' => $singleCurrencyAggregate?->getAttribute('last_paid_at'),
            'currencyTotals' => $currencyTotals,
        ];

        return view('admin.customers.show', [
            'tenant' => $tenant,
            'customer' => $customer,
            'bookings' => $bookings,
            'payments' => $payments,
            'metrics' => $metrics,
        ]);
    }

    public function update(Request $request, Tenant $tenant, int $customer): RedirectResponse
    {
        $customerModel = Customer::withoutGlobalScopes()
            ->where('tenant_id', $tenant->getKey())
            ->findOrFail($customer);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
        ]);

        app(CurrentTenant::class)->run($tenant, function () use ($customerModel, $validated): void {
            $customerModel->forceFill([
                'name' => $validated['name'],
                'phone' => $validated['phone'] ?? null,
                'email' => $validated['email'] ?? null,
            ])->save();
        });

        app(AuditLogger::class)->log('platform.customer_updated', $customerModel, [
            'tenant_id' => (int) $tenant->getKey(),
            'customer_id' => (int) $customerModel->getKey(),
        ]);

        return back()->with('status', __('Customer updated successfully.'));
    }

    public function toggleVip(Tenant $tenant, int $customer): RedirectResponse
    {
        $customer = Customer::withoutGlobalScopes()
            ->where('tenant_id', $tenant->getKey())
            ->findOrFail($customer);

        $next = ! $customer->is_vip;

        app(CurrentTenant::class)->run($tenant, function () use ($customer, $next): void {
            $customer->forceFill(['is_vip' => $next])->save();
        });

        app(AuditLogger::class)->log(
            $next ? 'platform.customer_vip_enabled' : 'platform.customer_vip_disabled',
            $customer,
            [
                'tenant_id' => (int) $tenant->getKey(),
                'customer_id' => (int) $customer->getKey(),
                'is_vip' => $next,
            ],
        );

        return back()->with(
            'status',
            $next ? __('platform.customer_vip_enabled') : __('platform.customer_vip_disabled'),
        );
    }
}
