<?php

namespace App\Core\Command\Output;

class Color
{
    const COLORS = [
        'black' => '0;30',
        'red' => '0;31',
        'green' => '0;32',
        'yellow' => '0;33',
        'blue' => '0;34',
        'magenta' => '0;35',
        'cyan' => '0;36',
        'white' => '0;37',
    ];

    /**
     * Applies the specified color to the given string using ANSI escape codes.
     *
     * @param string $str The string to colorize.
     * @param string $color The color to apply.
     * @return string The colorized string.
     */
    public static function apply(string $str, string $color): string
    {
        $colorCode = self::COLORS[$color] ?? '0';
        return "\033[" . $colorCode . "m" . $str . "\033[0m";
    }
}