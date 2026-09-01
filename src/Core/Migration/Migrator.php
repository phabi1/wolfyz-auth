<?php

namespace App\Core\Migration;

use App\Core\Migration\MigrationInterface;

class Migrator
{
    private $plugin;

    private $dir;

    private $ns;

    public function __construct(string $plugin, string $dir, string $ns)
    {
        $this->plugin = $plugin;
        $this->dir = $dir;
        $this->ns = $ns;
    }


    public function run(string $version)
    {
        $currentVersion = self::getCurrentVersion($this->plugin);
        $versions = $this->getVersions();
        $newVersions = array_filter($versions, function ($v) use ($currentVersion) {
            return version_compare($v, $currentVersion, '>');
        });

        foreach ($newVersions as $newVersion) {
            $this->runProcess($newVersion);
            $this->markVersionAsUpdated($newVersion);
            $currentVersion = $newVersion;
        }

        if ($currentVersion !== $version) {
            $this->markVersionAsUpdated($version);
        }
    }

    private function getVersions()
    {
        $files = glob($this->dir . '/inc/Migration/Migration_*.php');
        $versions = [];
        foreach ($files as $file) {
            $version = $this->extractVersionFromFile($file);
            if ($version) {
                $versions[] = $version;
            }
        }

        usort($versions, function ($a, $b) {
            return version_compare($a, $b);
        });

        return $versions;
    }

    private function runProcess($version)
    {
        $process = $this->getProcessForVersion($version);
        try {
            $process->up();
        } catch (\Exception $e) {
            $process->down();
        }

    }

    private function extractVersionFromFile($filename)
    {
        if (preg_match('/Migration_(\d+_\d+_\d+)\.php$/', $filename, $matches)) {
            return str_replace('_', '.', $matches[1]);
        }
        return null;
    }

    private function getProcessForVersion($version)
    {
        $className = "{$this->ns}\\Migration\\Migration_" . str_replace('.', '_', $version);
        if (!class_exists($className)) {
            throw new \Exception("Migration class {$className} does not exist");
        }
        $process = new $className();
        if ($process instanceof MigrationInterface) {
            return $process;
        }
        return $process;
    }

    private function markVersionAsUpdated($version)
    {
        update_option(str_replace('-', '_', "{$this->plugin}_version"), $version);
    }

    public static function getCurrentVersion(string $plugin): string
    {
        return get_option(str_replace('-', '_', "{$plugin}_version"), '0.0.0');
    }

    public static function isUpToDate(string $plugin, string $newVersion): bool
    {
        $currentVersion = self::getCurrentVersion($plugin);
        return version_compare($newVersion, $currentVersion, '<=');
    }

    public static function upgrade(string $plugin, string $dir, string $ns, string $newVersion)
    {
        if (!self::isUpToDate($plugin, $newVersion)) {
            $migrator = new self($plugin, $dir, $ns);
            $migrator->run($newVersion);
        }
    }
}