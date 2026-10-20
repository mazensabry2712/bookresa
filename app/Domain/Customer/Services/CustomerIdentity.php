<?php

namespace App\Domain\Customer\Services;

final class CustomerIdentity
{
    public function normalizePhone(?string $phone): ?string
    {
        if ($phone === null || trim($phone) === '') {
            return null;
        }

        $normalized = preg_replace('/\D+/', '', $phone);

        return $normalized !== '' ? $normalized : null;
    }
}
