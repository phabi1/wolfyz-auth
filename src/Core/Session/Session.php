<?php

namespace App\Core\Session;

use App\Core\Session\Attribute\AttributeBagInterface;
use App\Core\Session\SessionBagInterface;
use App\Core\Session\Attribute\AttributeBag;
use App\Core\Session\Storage\SessionStorageInterface;
use App\Core\Session\Storage\NativeSessionStorage;

/**
 * Thin wrapper around PHP's native session so the rest of the app never
 * touches $_SESSION directly.
 */
class Session
{

    private readonly SessionStorageInterface $storage;

    private $attributeName = 'attributes';

    public function __construct(?SessionStorageInterface $storage = null)
    {
        $this->storage = $storage ?? new NativeSessionStorage();
        session_register_shutdown();
        $this->storage->registerBag(new AttributeBag());
    }

    public function start(): bool
    {
        return $this->storage->start();
    }

    public function isStarted(): bool
    {
        return $this->storage->isStarted();
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $bag = $this->getAttributeBag();
        if (!$bag->has($key)) {
            return $default;
        }
        return $bag->get($key);
    }

    public function set(string $key, mixed $value): void
    {
        $this->getAttributeBag()->set($key, $value);
    }

    public function has(string $key): bool
    {
        return $this->getAttributeBag()->has($key);
    }

    public function remove(string $key): void
    {
        $this->getAttributeBag()->remove($key);
    }

    public function regenerate(bool $deleteOldSession = true): bool
    {
        return $this->storage->regenerate($deleteOldSession);
    }

    public function save(): void
    {
        $this->storage->save();
    }

    public function clear(): void
    {
        $this->storage->clear();
    }

    public function registerBag(SessionBagInterface $bag): void
    {
        $this->storage->registerBag($bag);
    }

    public function getBag(string $name): ?SessionBagInterface
    {
        return $this->storage->getBag($name);
    }

    private function getAttributeBag(): AttributeBagInterface
    {
        return $this->storage->getBag($this->attributeName);
    }
}
