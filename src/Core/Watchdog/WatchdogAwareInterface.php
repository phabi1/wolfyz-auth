<?php

namespace App\Core\Watchdog;

interface WatchdogAwareInterface
{
    public function setWatchdogService(WatchdogService $watchdogService): void;
}