<?php

namespace App\Http\Controllers\Auth;

use App\Domain\Identity\Services\EmailVerificationOtpService;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\VerifyEmailResponse as VerifyEmailResponseContract;

final class EmailVerificationOtpController
{
    public function verify(
        Request $request,
        EmailVerificationOtpService $otp,
    ): JsonResponse|RedirectResponse {
        $validated = $request->validate([
            'code' => ['required', 'string', 'regex:/\A\d{6}\z/'],
        ]);

        /** @var mixed $authenticatedUser */
        $authenticatedUser = $request->user();
        abort_unless($authenticatedUser instanceof User, 403);
        $user = $authenticatedUser;

        if ($user->hasVerifiedEmail()) {
            return app(VerifyEmailResponseContract::class)->toResponse($request);
        }

        /** @var array{code: string} $validated */
        $otp->validateCode($user, $validated['code']);

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        $otp->forget($user);

        return app(VerifyEmailResponseContract::class)->toResponse($request);
    }

    public function resend(
        Request $request,
        EmailVerificationOtpService $otp,
    ): JsonResponse|RedirectResponse {
        /** @var mixed $authenticatedUser */
        $authenticatedUser = $request->user();
        abort_unless($authenticatedUser instanceof User, 403);
        $user = $authenticatedUser;

        if ($user->hasVerifiedEmail()) {
            return app(VerifyEmailResponseContract::class)->toResponse($request);
        }

        if (! $otp->send($user)) {
            return back()->withErrors([
                'code' => __('app.verification_code_resend_wait'),
            ]);
        }

        return back()->with('status', 'verification-code-sent');
    }
}
