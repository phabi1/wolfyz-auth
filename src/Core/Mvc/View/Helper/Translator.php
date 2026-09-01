<?php

namespace App\Core\Mvc\View\Helper;

use App\Core\Translation\Translator as CoreTranslator;

class Translator
{
    private $translator;

    public function __construct(CoreTranslator $translator)
    {
        $this->translator = $translator;
    }

    public function __invoke(string $key, $vars = [], ?string $domain = null, ?string $locale = null): string
    {
        return $this->translator->translate($key, $vars, $domain, $locale);
    }
}