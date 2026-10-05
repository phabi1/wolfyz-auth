<?php

namespace App\Core\Helper;

class EmailHelper {
    public function sanitize(?string $value): string {
        if ($value === null) {
            return '';
        }

        return filter_var(trim($value), FILTER_SANITIZE_EMAIL);
    }

    public function isValid(?string $value): bool {
        if ($value === null) {
            return false;
        }

        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }
}