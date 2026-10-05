<?php

namespace App\Core\Mvc\View\Helper;

class Vite
{
    public function __invoke($args)
    {
        return $this->asset($args);
    }

    public function asset(string $entry): string
    {
        if (APP_ENV === 'development') {
            $devServer = rtrim(getenv('VITE_DEV_SERVER_URL') ?: 'http://localhost:5173', '/');
            $clientUrl = htmlspecialchars($devServer . '/@vite/client', ENT_QUOTES, 'UTF-8');
            $entryUrl = htmlspecialchars($devServer . '/' . ltrim($entry, '/'), ENT_QUOTES, 'UTF-8');
            return <<<HTML
            <script type="module" src="{$clientUrl}"></script>
            <script type="module" src="{$entryUrl}"></script>
        HTML;
        }

        $manifestPath = APP_DIR . '/public/dist/manifest.json';
        if (!file_exists($manifestPath)) {
            throw new \RuntimeException('Vite manifest not found. Run npm run build before serving production.');
        }

        $manifest = json_decode(file_get_contents($manifestPath), true, 512, JSON_THROW_ON_ERROR);
        if (!isset($manifest[$entry]['file'])) {
            throw new \RuntimeException('Vite entry not found in manifest: ' . $entry);
        }
        $jsFile = $manifest[$entry]['file'];
        $cssFiles = $manifest[$entry]['css'] ?? [];

        $html = '';
        foreach ($cssFiles as $css) {
            $html .= '<link rel="stylesheet" href="/dist/' . htmlspecialchars($css, ENT_QUOTES, 'UTF-8') . '">';
        }
        if ($jsFile) {
            $html .= '<script type="module" src="/dist/' . htmlspecialchars($jsFile, ENT_QUOTES, 'UTF-8') . '"></script>';
        }

        return $html;
    }
}