<?php

namespace App\Core\Lib;

class Bag
{
    private array $items = [];

    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    public function set(string $key, $value): void
    {
        $this->items[$key] = $value;
    }

    public function get(string $key)
    {
        return $this->items[$key] ?? null;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->items);
    }

    public function remove(string $key): void
    {
        unset($this->items[$key]);
    }

    public function clear(): void
    {
        $this->items = [];
    }

    public function all(): array
    {
        return $this->items;
    }
}