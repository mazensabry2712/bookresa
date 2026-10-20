<?php

namespace App\Console\Commands;

use App\Domain\Booking\Actions\UpdateBookingStatus;
use App\Domain\Booking\Enums\BookingStatus;
use App\Domain\Booking\Models\Booking;
use App\Domain\Payment\Contracts\PaymentGateway;
use App\Domain\Payment\Data\PaymentGatewayResult;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Payment\Models\Payment;
use App\Domain\Payment\Services\PaymentService;
use App\Domain\Payment\Services\SyncBookingPaymentStatus;
use App\Domain\Tenant\Models\Tenant;
use App\Domain\Tenant\Services\CurrentTenant;
use App\Support\PlatformSettings;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Throwable;

final class ExpirePendingBookingPaymentsCommand extends Command
{
    protected $signature = 'bookresa:expire-pending-booking-payments';

    protected $description = 'Verify expired online booking sessions and release pending slots only after a trusted terminal result.';

    public function handle(
        CurrentTenant $currentTenant,
        PaymentGateway $gateway,
        PaymentService $payments,
        SyncBookingPaymentStatus $bookingPaymentSync,
        UpdateBookingStatus $bookingStatus,
        PlatformSettings $settings,
    ): int {
        $provider = (string) config('bookresa.payments.default_provider', 'kashier');
        $bookingType = (new Booking)->getMorphClass();
        $checked = 0;
        $released = 0;
        $errors = 0;

        Payment::withoutGlobalScopes()
            ->select('payments.*')
            ->join('bookings as related_bookings', function ($join): void {
                $join->on('related_bookings.id', '=', 'payments.payable_id')
                    ->on('related_bookings.tenant_id', '=', 'payments.tenant_id');
            })
            ->where('payments.payable_type', $bookingType)
            ->where('payments.provider', $provider)
            ->where('related_bookings.status', BookingStatus::Pending->value)
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('payments as newer_payments')
                    ->whereColumn('newer_payments.tenant_id', '=', 'payments.tenant_id')
                    ->whereColumn('newer_payments.payable_type', '=', 'payments.payable_type')
                    ->whereColumn('newer_payments.payable_id', '=', 'payments.payable_id')
                    ->whereColumn('newer_payments.id', '>', 'payments.id');
            })
            ->where(function ($query): void {
                $query->whereIn('payments.status', [
                    PaymentStatus::Failed->value,
                    PaymentStatus::Cancelled->value,
                ])->orWhere(function ($expired): void {
                    $expired->whereIn('payments.status', [
                        PaymentStatus::Pending->value,
                        PaymentStatus::Processing->value,
                    ])->whereNotNull('payments.expires_at')
                        ->where('payments.expires_at', '<=', now());
                });
            })
            ->orderBy('payments.id')
            ->chunkById(100, function (Collection $batch) use (
                $currentTenant,
                $gateway,
                $payments,
                $bookingPaymentSync,
                $bookingStatus,
                $settings,
                &$checked,
                &$released,
                &$errors,
            ): void {
                /** @var Collection<int, Payment> $batch */
                foreach ($batch as $candidate) {
                    $tenant = Tenant::query()->find((int) $candidate->tenant_id);

                    if ($tenant === null) {
                        continue;
                    }

                    try {
                        $didRelease = $currentTenant->run($tenant, function () use (
                            $candidate,
                            $gateway,
                            $payments,
                            $bookingPaymentSync,
                            $bookingStatus,
                            $settings,
                            &$checked,
                        ): bool {
                            $booking = Booking::query()
                                ->whereKey($candidate->payable_id)
                                ->first();

                            if (! $booking instanceof Booking || $booking->status !== BookingStatus::Pending) {
                                return false;
                            }

                            $latest = $booking->payments()->latest('id')->first();

                            if ($latest === null || (int) $latest->getKey() !== (int) $candidate->getKey()) {
                                return false;
                            }

                            $payment = Payment::query()->whereKey($candidate->getKey())->firstOrFail();

                            if (in_array($payment->status, [PaymentStatus::Pending, PaymentStatus::Processing], true)) {
                                if (
                                    $payment->expires_at === null
                                    || $payment->expires_at->isFuture()
                                    || blank($payment->provider_reference)
                                ) {
                                    return false;
                                }

                                try {
                                    $result = $gateway->verifyPayment($payment->provider_reference);
                                } catch (Throwable $exception) {
                                    report($exception);
                                    return false;
                                }

                                if (! $this->verifiedResultMatches($payment, $result, $settings)) {
                                    report(new \RuntimeException('Expired booking payment verification did not match the stored merchant, order, amount or currency.'));
                                    return false;
                                }

                                $payment = $payments->applyResult($payment, $result);
                                $bookingPaymentSync->handle($payment, $payment->status);
                                $checked++;
                            }

                            if (! in_array($payment->status, [PaymentStatus::Failed, PaymentStatus::Cancelled], true)) {
                                return false;
                            }

                            $booking = Booking::query()->whereKey($candidate->payable_id)->firstOrFail();

                            if ($booking->status !== BookingStatus::Pending) {
                                return false;
                            }

                            $hasPaidAttempt = $booking->payments()
                                ->where('status', PaymentStatus::Paid->value)
                                ->exists();

                            $hasActiveProviderAttempt = $booking->payments()
                                ->whereIn('status', [
                                    PaymentStatus::Pending->value,
                                    PaymentStatus::Processing->value,
                                ])
                                ->whereNotNull('provider_reference')
                                ->where(function ($query): void {
                                    $query->whereNull('expires_at')
                                        ->orWhere('expires_at', '>', now());
                                })
                                ->exists();

                            if ($hasPaidAttempt || $hasActiveProviderAttempt) {
                                return false;
                            }

                            $bookingStatus->handle(
                                $booking,
                                BookingStatus::Cancelled,
                                Booking::EXPIRED_ONLINE_PAYMENT_CANCELLATION_REASON,
                            );

                            return true;
                        });

                        if ($didRelease) {
                            $released++;
                        }
                    } catch (Throwable $exception) {
                        report($exception);
                        $errors++;
                    }
                }
            }, 'payments.id', 'id');

        $this->info("Checked {$checked} expired payment session(s); released {$released} booking slot(s); {$errors} processing error(s).");

        return $errors === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function verifiedResultMatches(
        Payment $payment,
        PaymentGatewayResult $result,
        PlatformSettings $settings,
    ): bool {
        if ($payment->provider !== 'kashier' || $result->providerReference !== $payment->provider_reference) {
            return false;
        }

        $centralized = filter_var(
            data_get($payment->metadata, 'centralized_payment', false),
            FILTER_VALIDATE_BOOL,
        );
        $providerConfig = $settings->kashierConfig();
        $expectedMerchant = $centralized
            ? trim((string) ($providerConfig['merchant_id'] ?? ''))
            : trim((string) data_get($payment->metadata, 'connected_account_merchant_id', ''));
        $metadata = $result->metadata;

        try {
            $verifiedAmountMinor = $this->amountToMinor(data_get($metadata, 'amount'));
        } catch (Throwable) {
            return false;
        }

        return $expectedMerchant !== ''
            && trim((string) data_get($metadata, 'merchant_id')) === $expectedMerchant
            && trim((string) data_get($metadata, 'merchant_order_id')) === $payment->reference
            && $verifiedAmountMinor === (int) $payment->amount_minor
            && strtoupper(trim((string) data_get($metadata, 'currency'))) === strtoupper((string) $payment->currency);
    }

    private function amountToMinor(mixed $amount): int
    {
        $value = trim((string) $amount);

        if (! preg_match('/^\d+(?:\.\d{1,2})?$/', $value)) {
            throw new \RuntimeException('Verified payment amount is invalid.');
        }

        [$major, $minor] = array_pad(explode('.', $value, 2), 2, '');

        return ((int) $major * 100) + (int) str_pad(substr($minor.'00', 0, 2), 2, '0');
    }
}
