<?php

namespace App\Http\Controllers\Platform;

use App\Domain\Booking\Enums\BookingStatus;
use App\Domain\Booking\Models\Booking;
use App\Domain\Customer\Models\Customer;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Payment\Models\Payment;
use App\Domain\Tenant\Models\Tenant;
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

        $paidPayments = Payment::withoutGlobalScopes()
            ->from('payments')
            ->join('bookings', function ($join): void {
                $join->on('bookings.id', '=', 'payments.payable_id')
                    ->where('payments.payable_type', '=', Booking::class);
            })
            ->whereColumn('bookings.customer_id', 'customers.id')
            ->whereColumn('bookings.tenant_id', 'customers.tenant_id')
            ->where('payments.status', PaymentStatus::Paid->value)
            ->when($currency !== '', fn ($query) => $query->where('payments.currency', $currency));

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

        $currencies = Payment::withoutGlobalScopes()
            ->where('status', PaymentStatus::Paid->value)
            ->distinct()
            ->orderBy('currency')
            ->pluck('currency');

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

        $bookings = Booking::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('customer_id', $customer->getKey())
            ->with([
                'service' => fn ($query) => $query->withoutGlobalScopes(),
                'staff' => fn ($query) => $query->withoutGlobalScopes(),
            ])
            ->latest('starts_at')
            ->limit(50)
            ->get();

        $bookingIds = $bookings->pluck('id');

        $payments = Payment::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('payable_type', Booking::class)
            ->whereIn('payable_id', $bookingIds->all())
            ->orderByDesc('paid_at')
            ->orderByDesc('id')
            ->get();

        $paidPayments = $payments->where('status', PaymentStatus::Paid);
        $refundedPayments = $payments->where('status', PaymentStatus::Refunded);

        $metrics = [
            'bookings' => $bookings->count(),
            'completedBookings' => $bookings->where('status', BookingStatus::Completed)->count(),
            'cancelledBookings' => $bookings->where('status', BookingStatus::Cancelled)->count(),
            'noShows' => $bookings->where('status', BookingStatus::NoShow)->count(),
            'totalSpentMinor' => (int) $paidPayments->sum('amount_minor'),
            'refundedMinor' => (int) $refundedPayments->sum('amount_minor'),
            'averagePaidMinor' => $paidPayments->count() > 0
                ? (int) round($paidPayments->sum('amount_minor') / $paidPayments->count())
                : 0,
            'firstSeenAt' => $customer->first_seen_at,
            'lastSeenAt' => $customer->last_seen_at,
            'lastPaidAt' => $paidPayments->max('paid_at'),
        ];

        return view('admin.customers.show', [
            'tenant' => $tenant,
            'customer' => $customer,
            'bookings' => $bookings,
            'payments' => $payments,
            'metrics' => $metrics,
        ]);
    }

    public function toggleVip(Tenant $tenant, int $customer): RedirectResponse
    {
        $customer = Customer::withoutGlobalScopes()
            ->where('tenant_id', $tenant->getKey())
            ->findOrFail($customer);

        $next = ! $customer->is_vip;
        $customer->forceFill(['is_vip' => $next])->save();

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
