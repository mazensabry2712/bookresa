<?php

namespace App\Http\Controllers\Payment;

use App\Domain\Billing\Models\Subscription;
use App\Domain\Booking\Models\Booking;
use App\Domain\Payment\Models\Payment;
use App\Domain\Payment\Services\PaymentService;
use App\Domain\Payment\Services\SyncBookingPaymentStatus;
use App\Domain\Payment\Services\SyncSubscriptionPaymentStatus;
use App\Infrastructure\Payments\Kashier\KashierGateway;
use App\Infrastructure\Payments\Kashier\KashierRedirectVerifier;
use App\Domain\Tenant\Services\CurrentTenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use RuntimeException;

final class KashierReturnController
{
    public function __invoke(
        Request $request,
        CurrentTenant $currentTenant,
        KashierRedirectVerifier $verifier,
        KashierGateway $gateway,
        PaymentService $payments,
        SyncBookingPaymentStatus $bookingPaymentSync,
        SyncSubscriptionPaymentStatus $subscriptionPaymentSync,
    ): RedirectResponse|JsonResponse {
        $query = $request->all();
        $apiKey = (string) config('bookresa.payments.kashier.api_key');

        if (! $verifier->verify($query, $apiKey)) {
            return response()->json(['message' => 'Invalid Kashier redirect signature.'], 401);
        }

        $reference = trim((string) ($query['orderReference'] ?? ''));

        if ($reference === '') {
            return response()->json(['message' => 'Kashier payment reference is missing.'], 422);
        }

        $payment = Payment::withoutGlobalScopes()
            ->where('provider', 'kashier')
            ->where('reference', $reference)
            ->first();

        if ($payment === null) {
            return response()->json(['message' => 'Payment was not found.'], 404);
        }

        try {
            $amountMinor = $this->amountToMinor($query['amount'] ?? null);
        } catch (RuntimeException) {
            return response()->json(['message' => 'Payment amount is invalid.'], 422);
        }

        if ($amountMinor !== (int) $payment->amount_minor) {
            return response()->json(['message' => 'Payment amount does not match.'], 422);
        }

        if (strtoupper((string) ($query['currency'] ?? '')) !== strtoupper((string) $payment->currency)) {
            return response()->json(['message' => 'Payment currency does not match.'], 422);
        }

        $tenant = $payment->tenant;

        if ($tenant === null) {
            return response()->json(['message' => 'Payment tenant was not found.'], 404);
        }

        return $currentTenant->run($tenant, function () use ($payment, $gateway, $payments, $bookingPaymentSync, $subscriptionPaymentSync, $query, $tenant): RedirectResponse {
            $notice = 'Payment is being verified.';

            if ($payment->provider_reference !== null) {
                try {
                    $result = $gateway->verifyPayment($payment->provider_reference);
                } catch (RuntimeException) {
                    $result = null;
                }

                if ($result !== null) {
                    $updated = $payments->applyResult($payment->fresh(), $result);
                    $bookingPaymentSync->handle($updated, $result->status);
                    $subscriptionPaymentSync->handle($updated, $result->status);

                    $notice = match ($updated->status->value) {
                        'paid' => 'Payment completed successfully.',
                        'failed' => 'Payment was not completed.',
                        default => 'Payment is being verified.',
                    };
                }
            }

            $payable = $payment->payable;

            if ($payable instanceof Subscription) {
                return redirect()
                    ->route('billing.subscription')
                    ->with('payment_notice', $notice);
            }

            if (! $payable instanceof Booking || blank($payable->booking_reference)) {
                return redirect()->route('home')->with('status', $notice);
            }

            return redirect()
                ->to(URL::signedRoute('public.booking.confirmation', [
                    'tenant' => $tenant->slug,
                    'booking' => $payable->booking_reference,
                ]))
                ->with('payment_notice', $notice);
        });
    }

    private function amountToMinor(mixed $amount): int
    {
        $value = trim((string) $amount);

        if (! preg_match('/^\d+(?:\.\d{1,2})?$/', $value)) {
            throw new RuntimeException('Kashier redirect amount is invalid.');
        }

        [$major, $minor] = array_pad(explode('.', $value, 2), 2, '');

        return ((int) $major) * 100 + (int) str_pad(substr($minor.'00', 0, 2), 2, '0');
    }
}
