<?php

namespace App\Http\Controllers\Payment;

use App\Domain\Payment\Services\HandleKashierWebhook;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use LogicException;
use RuntimeException;

class KashierWebhookController
{
    public function __invoke(Request $request, HandleKashierWebhook $handler): JsonResponse|Response
    {
        try {
            $processed = $handler->handle(
                $request->json()->all(),
                (string) $request->header('x-kashier-signature'),
            );
        } catch (LogicException $exception) {
            return response()->json(['message' => $exception->getMessage()], 401);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return $processed
            ? response()->noContent()
            : response()->noContent(409);
    }
}
