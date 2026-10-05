<?php

namespace App\Core\Session\Storage;

use App\Core\Session\SessionBagInterface;

interface SessionStorageInterface
{
    public function start(): bool;

    public function isStarted(): bool;

    public function clear(): void;

    public function regenerate(bool $destroy = false, ?int $lifetime = null): bool;

    public function save(): void;

    public function registerBag(SessionBagInterface $bag): void;

    public function getBag($name): ?SessionBagInterface;
}