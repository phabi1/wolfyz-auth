<?php

namespace App\Core\Watchdog;

class WatchdogService
{
    const LEVEL_INFO = 'info';
    const LEVEL_WARNING = 'warning';
    const LEVEL_ERROR = 'error';
    const LEVEL_DEBUG = 'debug';

    private static $_levels = [self::LEVEL_INFO, self::LEVEL_WARNING, self::LEVEL_ERROR, self::LEVEL_DEBUG];
    private Storage\StorageInterface $storage;

    private array $levels;

    public function __construct(Storage\StorageInterface $storage, ?array $levels = [])
    {
        $this->storage = $storage;
        $this->levels = !empty($levels) ? $levels : self::$_levels;
    }

    public function info(string $message, array $data = [])
    {
        $this->log($message, $data, self::LEVEL_INFO);
    }

    public function warning(string $message, array $data = [])
    {
        $this->log($message, $data, self::LEVEL_WARNING);
    }

    public function error(string $message, array $data = [])
    {
        $this->log($message, $data, self::LEVEL_ERROR);
    }

    public function debug(string $message, array $data = [])
    {
        $this->log($message, $data, self::LEVEL_DEBUG);
    }

    /**
     * Logs a message with the given level if the level is enabled.
     */
    private function log(string $message, array $data = [], string $level = self::LEVEL_INFO)
    {
        if (!in_array($level, $this->levels)) {
            return;
        }
        $this->storage->log($message, $data, $level);
    }
}