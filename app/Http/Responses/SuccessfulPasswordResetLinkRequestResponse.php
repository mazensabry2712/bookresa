<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Contracts\SuccessfulPasswordResetLinkRequestResponse as SuccessfulPasswordResetLinkRequestResponseContract;

final class SuccessfulPasswordResetLinkRequestResponse implements SuccessfulPasswordResetLinkRequestResponseContract
{
    public function __construct(protected string $status)
    {
    }

    public function toResponse($request): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $message = __('app.password_reset_link_sent');

        return $request->wantsJson()
            ? new JsonResponse(['message' => $message], 200)
            : redirect()->route('password.request')->with('status', $message);
    }
}
