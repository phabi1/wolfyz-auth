<?php

namespace App\Core\Translation;

class Translator
{
    private $locale = 'fr_FR';
    private $messages = [];

    public function setLocale(string $locale)
    {
        $this->locale = $locale;
    }

    public function translate(string $key, array $vars = [], ?string $domain = null, ?string $locale = null)
    {
        $locale = $locale ?? $this->locale;
        $domain = $domain ?? 'default';
        $this->loadDomain($domain, $locale);

        if (isset($this->messages[$domain][$locale][$key])) {
            $message = $this->messages[$domain][$locale][$key];
        } else {
            $message = $key;
        }

        return $this->format($message, $vars);
    }

    private function format($message, array $vars)
    {
        $formattedParams = [];
        foreach ($vars as $key => $value) {
            $formattedParams['{' . $key . '}'] = $value;
        }
        return strtr($message, $formattedParams);
    }

    private function loadDomain(string $domain, string $locale)
    {
        if (!isset($this->messages[$domain][$locale])) {
            $messages = require APP_DIR . "/translations/{$domain}.{$locale}.php";
            // Load the domain messages for the given locale
            $this->messages[$domain][$locale] = $messages;
        }
    }
}