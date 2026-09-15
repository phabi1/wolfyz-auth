<?php

namespace App\Core\Mvc\View\Helper;

class Vite
{

    private $manifest;

    public function __invoke($args)
    {
        return $this->asset($args);
    }

    public function asset(string $entry): string
    {
        $isDev = APP_ENV === 'development'; // Vérifie si l'environnement est en développement

        if ($isDev) {
            return <<<HTML
            <script type="module" src="http://localhost:5173/@vite/client"></script>
            <script type="module" src="http://localhost:5173/{$entry}"></script>
        HTML;
        }

        // En production : lire le manifest généré par Vite
        $this->loadManifest();

        $jsFile = $this->manifest[$entry]['file'] ?? '';
        $cssFiles = $this->manifest[$entry]['css'] ?? [];

        $html = '';
        foreach ($cssFiles as $css) {
            $html .= '<link rel="stylesheet" href="/dist/' . $css . '">';
        }
        if ($jsFile) {
            $html .= '<script type="module" src="/dist/' . $jsFile . '"></script>';
        }

        return $html;
    }

    /**
     * Load the Vite manifest file if it hasn't been loaded yet.
     */
    private function loadManifest(): void
    {
        if ($this->manifest === null) {
            $manifestPath = APP_DIR . '/public/dist/.vite/manifest.json';
            if (file_exists($manifestPath)) {
                $this->manifest = json_decode(file_get_contents($manifestPath), true);
            } else {
                $this->manifest = [];
            }
        }
    }
}