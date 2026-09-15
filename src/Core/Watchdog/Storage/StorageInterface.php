<?php

namespace App\Core\Watchdog\Storage;

interface StorageInterface
{
    public function log(string $message, array $data = [], string $level = 'info');
}