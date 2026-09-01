<?php

namespace App\Core\Helper;

class StringHelper
{
    public function sanitize(string $string): string
    {
        return filter_var($string, FILTER_SANITIZE_STRING);
    }

    public function truncate(string $string, int $length = 100, string $append = '...'): string
    {
        if (strlen($string) <= $length) {
            return $string;
        }
        return substr($string, 0, $length) . $append;
    }

    public function slug(string $string): string
    {
        $slug = remove_accents($string, 'fr_FR');

        $slug = strtolower(trim($slug));

        $slug = preg_replace('/[^a-z0-9-]+/', '-', $slug);
        return $slug;
    }

    public function startsWith(string $string, string $start): bool
    {
        return substr($string, 0, strlen($start)) === $start;
    }

    public function endsWith(string $string, string $end): bool
    {
        return substr($string, -strlen($end)) === $end;
    }
}