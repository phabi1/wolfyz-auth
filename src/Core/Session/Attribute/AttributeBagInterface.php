<?php

namespace App\Core\Session\Attribute;

use App\Core\Session\SessionBagInterface;

interface AttributeBagInterface extends SessionBagInterface
{
    /**
     * Checks if an attribute is defined.
     */
    public function has(string $name): bool;

    /**
     * Returns an attribute.
     */
    public function get(string $name, mixed $default = null): mixed;

    /**
     * Sets an attribute.
     */
    public function set(string $name, mixed $value): void;

    /**
     * Returns attributes.
     *
     * @return array<mixed>
     */
    public function all(): array;

    public function replace(array $attributes): void;

    /**
     * Removes an attribute.
     *
     * @return mixed The removed value or null when it does not exist
     */
    public function remove(string $name): mixed;
}