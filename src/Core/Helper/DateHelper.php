<?php

namespace App\Core\Helper;

class DateHelper
{
    const FORMAT_DEFAULT = 'Y-m-d';

    const FORMAT_DMY = 'd/m/Y';

    const FORMAT_DMYHI = 'd/m/Y H:i';

    public function convertToTimestamp(string $dateString, ?string $format = null): int
    {
        if (!$format) {
            $format = $this->detectFormat($dateString);
        }
        $timestamp = strtotime($dateString);
        if ($timestamp === false) {
            throw new \InvalidArgumentException("Invalid date string: $dateString");
        }
        return $timestamp;
    }

    public function formatDay($day)
    {
        switch ($day) {
            case 0:
                return 'Dimanche';
            case 1:
                return 'Lundi';
            case 2:
                return 'Mardi';
            case 3:
                return 'Mercredi';
            case 4:
                return 'Jeudi';
            case 5:
                return 'Vendredi';
            case 6:
                return 'Samedi';
            default:
                return '';
        }
    }

    private function detectFormat(string $dateString): string
    {
        // Check for DMY format (e.g., 31/12/2023)
        if (preg_match('/^\d{2}\/\d{2}\/\d{4}/', $dateString)) {
            return self::FORMAT_DMY;
        }

        // Check for DMY with time format (e.g., 31/12/2023 23:59)
        if (preg_match('/^\d{2}\/\d{2}\/\d{4} \d{2}:\d{2}/', $dateString)) {
            return self::FORMAT_DMYHI;
        }

        // Default to Y-m-d format
        return self::FORMAT_DEFAULT;
    }
}