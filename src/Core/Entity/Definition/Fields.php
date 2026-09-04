<?php

namespace App\Core\Entity\Definition;

class Fields implements \ArrayAccess, \IteratorAggregate
{
    private $fields = [];

    public function getFields()
    {
        return $this->fields;
    }

    public function get($name): ?Field
    {
        return $this->fields[$name] ?? null;
    }

    public function set($name, Field $field)
    {
        $this->fields[$name] = $field;
    }

    public function remove($name)
    {
        unset($this->fields[$name]);
    }

    public function has($name): bool
    {
        return isset($this->fields[$name]);
    }

    public function offsetExists(mixed $offset): bool
    {
        return $this->has($offset);
    }

    public function offsetGet(mixed $offset): ?Field
    {
        return $this->get($offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->set($offset, $value);
    }

    public function offsetUnset(mixed $offset): void
    {
        $this->remove($offset);
    }

        public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->fields);
    }

}