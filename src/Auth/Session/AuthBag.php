<?php

namespace App\Auth\Session;

use App\Core\Session\SessionBagInterface;

class AuthBag implements SessionBagInterface
{
    private array $storage = [];

    public function getName(): string
    {
        return 'auth';
    }

    public function getStorageKey(): string
    {
        return 'auth';
    }

    public function initialize(array &$storage): void
    {
        $this->storage = &$storage;
    }

    public function clear(): mixed
    {
        $keys = array_keys($this->storage);
        foreach ($keys as $key) {
            $this->remove($key);
        }
        return null;
    }

    public function get(string $key, $default = null)
    {
        return $this->storage[$key] ?? $default;
    }

    public function set(string $key, $value): void
    {
        $this->storage[$key] = $value;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->storage);
    }

    public function remove(string $key): void
    {
        unset($this->storage[$key]);
    }
}