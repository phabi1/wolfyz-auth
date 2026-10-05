<?php

namespace App\Core\Helper;

class PhoneHelper
{
    public function sanitize(?string $phone): ?string
    {
        if ($phone === null) {
            return null;
        }

        // Remove all non-digit characters
        $sanitizedPhone = preg_replace('/\D+/', '', $phone);

        // If the phone number starts with '0', replace it with '+33'
        if (strpos($sanitizedPhone, '0') === 0) {
            $sanitizedPhone = '+33' . substr($sanitizedPhone, 1);
        }

        return $sanitizedPhone;
    }

    public function isValid(?string $phone): bool
    {
        if ($phone === null) {
            return false;
        }

        // Basic validation for French phone numbers
        return preg_match('/^(\+33|0)[1-9](\d{2}){4}$/', $phone) === 1;
    }
}