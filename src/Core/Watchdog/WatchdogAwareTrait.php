<?php
namespace App\Core\Watchdog;

trait WatchdogAwareTrait
{
    protected ?WatchdogService $watchdogService = null;

    public function setWatchdogService(WatchdogService $watchdogService): void
    {
        $this->watchdogService = $watchdogService;
    }
}