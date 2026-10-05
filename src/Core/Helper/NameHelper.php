<?php

namespace App\Core\Helper;

class NameHelper
{
    /**
     * Fornat firstname
     * 
     * @example "john" becomes "John"
     * @example "Jean marie" becomes "Jean Marie"
     * @param string $value
     * @return string
     */
    public function firstname(string $value)
    {
        // manage capitalization for beginning of the name
        $words = explode(' ', $value);
        $words = array_map(function($word) {
            return ucfirst(strtolower($word));
        }, $words);

        return implode(' ', $words);
    }

    public function lastname(string $value)
    {
        return strtoupper($value);
    }
}