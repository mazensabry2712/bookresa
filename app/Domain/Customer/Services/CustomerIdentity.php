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

        if ($normalized === '') {
            return null;
        }

        if (str_starts_with($normalized, '00')) {
            $normalized = substr($normalized, 2);
        }

        if (str_starts_with($normalized, '0') && strlen($normalized) === 11) {
            return '20'.substr($normalized, 1);
        }

        return $normalized;
    }
}
