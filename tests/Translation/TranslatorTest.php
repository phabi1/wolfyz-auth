<?php

namespace App\Tests\Translation;

use App\Core\Translation\Translator;
use PHPUnit\Framework\TestCase;

class TranslatorTest extends TestCase
{
    protected function setUp(): void
    {
        if (!defined('APP_DIR')) {
            define('APP_DIR', dirname(__DIR__, 2));
        }
    }

    public function testFrenchMessagesAndPlaceholders(): void
    {
        $translator = new Translator();
        self::assertSame('Se connecter', $translator->translate('Sign in'));
        self::assertSame('Nouveau mot de passe', $translator->translate('New password'));
        self::assertSame('L\'équipe Roller Les Loups', $translator->translate('Team {siteName}', [
            'siteName' => 'Roller Les Loups',
        ]));
        self::assertSame('An unknown message', $translator->translate('An unknown message'));
    }

    public function testCatalogueCoversEveryLiteralTranslationKey(): void
    {
        $messages = require APP_DIR . '/translations/default.fr_FR.php';
        foreach (['src', 'views'] as $directory) {
            $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(APP_DIR . '/' . $directory));
            foreach ($files as $file) {
                if (!$file->isFile() || $file->getExtension() !== 'php') {
                    continue;
                }
                preg_match_all('/->translate\(\s*([\'"])(.*?)\1/s', file_get_contents($file->getPathname()), $matches);
                foreach ($matches[2] as $key) {
                    self::assertArrayHasKey($key, $messages, $file->getPathname());
                    self::assertNotSame('', $messages[$key], $key);
                    preg_match_all('/\{[^{}]+\}/', $key, $sourcePlaceholders);
                    preg_match_all('/\{[^{}]+\}/', $messages[$key], $translatedPlaceholders);
                    self::assertEqualsCanonicalizing($sourcePlaceholders[0], $translatedPlaceholders[0], $key);
                }
            }
        }
    }
}
