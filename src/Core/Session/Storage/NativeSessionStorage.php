<?php

namespace App\Core\Session\Storage;

use App\Core\Session\SessionBagInterface;

class NativeSessionStorage implements SessionStorageInterface
{

    private array $bags = [];

    protected bool $started = false;
    protected bool $closed = false;

    public function start(): bool
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return true;
        }
        if (!session_start()) {
            return false;
        }
        $this->loadSession();
        return true;
    }

    public function isStarted(): bool
    {
        return session_status() === PHP_SESSION_ACTIVE;
    }

    public function clear(): void
    {
        foreach ($this->bags as $bag) {
            $bag->clear();
        }
        $_SESSION = [];
        session_destroy();
    }

    public function regenerate(bool $destroy = false, ?int $lifetime = null): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return false;
        }
        return session_regenerate_id($destroy);
    }

    public function save(): void
    {
        session_write_close();
    }

    public function registerBag(SessionBagInterface $bag): void
    {
        if ($this->isStarted()) {
            throw new \RuntimeException('Cannot register a session bag after the session has started.');
        }
        $this->bags[$bag->getName()] = $bag;
    }

    public function getBag($name): ?SessionBagInterface
    {
        return $this->bags[$name] ?? null;
    }

    private function loadSession(?array &$session = null): void
    {
        if (null === $session) {
            $session = &$_SESSION;
        }

        foreach ($this->bags as $bag) {
            $key = $bag->getStorageKey();
            $session[$key] = isset($session[$key]) && \is_array($session[$key]) ? $session[$key] : [];
            $bag->initialize($session[$key]);
        }
        $this->started = true;
        $this->closed = false;
    }
}