<?php

namespace App\Core\Session\Attribute;


class AttributeBag implements AttributeBagInterface
{
    private $data = [];
    private $name = 'attributes';

    public function getName(): string
    {
        return $this->name;
    }

    public function getStorageKey(): string
    {
        return $this->name;
    }

    public function initialize(array &$data): void
    {
        $this->data = &$data;
    }

    public function has(string $name): bool
    {
        return \array_key_exists($name, $this->data);
    }

    public function get(string $name, mixed $default = null): mixed
    {
        return \array_key_exists($name, $this->data) ? $this->data[$name] : $default;
    }

    public function set(string $name, mixed $value): void
    {
        $this->data[$name] = $value;
    }

    public function all(): array
    {
        return $this->data;
    }

    public function replace(array $attributes): void
    {
        $this->data = [];
        foreach ($attributes as $key => $value) {
            $this->set($key, $value);
        }
    }

    public function remove(string $name): mixed
    {
        $retval = null;
        if (\array_key_exists($name, $this->data)) {
            $retval = $this->data[$name];
            unset($this->data[$name]);
        }

        return $retval;
    }

    public function clear(): mixed
    {
        $return = $this->data;
        $this->data = [];

        return $return;
    }

    /**
     * Returns an iterator for attributes.
     *
     * @return \ArrayIterator<string, mixed>
     */
    public function getIterator(): \ArrayIterator
    {
        return new
            /** @extends \ArrayIterator<string, mixed> */
            class ($this->data) extends \ArrayIterator {
            public function key(): string
            {
                return (string) parent::key();
            }
        };
    }

    /**
     * Returns the number of attributes.
     */
    public function count(): int
    {
        return \count($this->data);
    }

}