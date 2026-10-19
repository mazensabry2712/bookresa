<?php

namespace App\Http\Controllers\Payment;

use App\Domain\Booking\Models\Booking;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Payment\Models\Payment;
use App\Domain\Tenant\Services\CurrentTenant;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class PaymentManagementController
{
    public function index(Request $request, CurrentTenant $currentTenant): View
    {
        $tenant = $currentTenant->get();
        abort_unless($tenant !== null, 404);

        $statusInput = $request->query('status');
        $status = is_string($statusInput) && PaymentStatus::tryFrom($statusInput) !== null
            ? $statusInput
            : '';

        $searchInput = $request->query('q');
        $search = is_string($searchInput) ? mb_substr(trim($searchInput), 0, 120) : '';

        $from = $this->validDate($request->query('from'));
        $to = $this->validDate($request->query('to'));

        if ($from !== null && $to !== null && $to < $from) {
            $to = null;
        }

        $timezone = (string) ($tenant->profile?->timezone ?: config('app.timezone', 'UTC'));

        try {
            new DateTimeZone($timezone);
        } catch (\Throwable) {
            $timezone = (string) (config('app.timezone') ?: 'UTC');
        }

        $paymentsQuery = Payment::query()
            ->where('payable_type', (new Booking)->getMorphClass())
            ->with(['payable.customer', 'payable.service', 'payable.staff'])
            ->when($status !== '', fn (Builder $query) => $query->where('payments.status', $status))
            ->when($from !== null, fn (Builder $query) => $query->whereRaw(
                'DATE(COALESCE(payments.paid_at, payments.created_at)) >= ?',
                [$from],
            ))
            ->when($to !== null, fn (Builder $query) => $query->whereRaw(
                'DATE(COALESCE(payments.paid_at, payments.created_at)) <= ?',
                [$to],
            ));

        if ($search !== '') {
            $pattern = '%'.$search.'%';

            $paymentsQuery->where(function (Builder $query) use ($pattern): void {
                $query
                    ->where('payments.reference', 'like', $pattern)
                    ->orWhere('payments.provider_reference', 'like', $pattern)
                    ->orWhereHasMorph('payable', [Booking::class], function (Builder $bookingQuery) use ($pattern): void {
                        $bookingQuery
                            ->where('booking_reference', 'like', $pattern)
                            ->orWhereHas('customer', function (Builder $customerQuery) use ($pattern): void {
                                $customerQuery
                                    ->where('name', 'like', $pattern)
                                    ->orWhere('phone', 'like', $pattern)
                                    ->orWhere('email', 'like', $pattern);
                            });
                    });
            });
        }

        $currencyStats = (clone $paymentsQuery)
            ->reorder()
            ->setEagerLoads([])
            ->select('currency')
            ->selectRaw('COUNT(*) as record_count')
            ->selectRaw(
                'SUM(CASE WHEN status IN (?, ?) THEN CASE WHEN amount_minor > refunded_amount_minor THEN amount_minor - refunded_amount_minor ELSE 0 END ELSE 0 END) as net_received_minor',
                [PaymentStatus::Paid->value, PaymentStatus::Refunded->value],
            )
            ->selectRaw(
                'SUM(CASE WHEN status IN (?, ?) THEN 1 ELSE 0 END) as awaiting_count',
                [PaymentStatus::Pending->value, PaymentStatus::Processing->value],
            )
            ->selectRaw(
                'SUM(CASE WHEN status IN (?, ?) THEN 1 ELSE 0 END) as attention_count',
                [PaymentStatus::Failed->value, PaymentStatus::Cancelled->value],
            )
            ->groupBy('currency')
            ->get();

        $payments = $paymentsQuery
            ->latest('payments.id')
            ->paginate(25)
            ->withQueryString();

        return view('payments.index', [
            'tenant' => $tenant,
            'payments' => $payments,
            'statuses' => PaymentStatus::cases(),
            'status' => $status,
            'search' => $search,
            'from' => $from ?? '',
            'to' => $to ?? '',
            'timezone' => $timezone,
            'currencyStats' => $currencyStats,
            'recordCount' => (int) $currencyStats->sum(fn ($row): int => (int) $row->record_count),
            'awaitingCount' => (int) $currencyStats->sum(fn ($row): int => (int) $row->awaiting_count),
            'attentionCount' => (int) $currencyStats->sum(fn ($row): int => (int) $row->attention_count),
            'netReceivedCurrencies' => $currencyStats
                ->filter(fn ($row): bool => (int) $row->net_received_minor > 0)
                ->values(),
            'hasFilters' => $status !== '' || $search !== '' || $from !== null || $to !== null,
        ]);
    }

    private function validDate(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date instanceof DateTimeImmutable && $date->format('Y-m-d') === $value
            ? $value
            : null;
    }
}
