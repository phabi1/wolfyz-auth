<?php

namespace App\Core\Entity\Definition;

class Relations implements \ArrayAccess, \IteratorAggregate
{

    private $relations = [];

    public function getRelations()
    {
        return $this->relations;
    }

    public function isEmpty(): bool
    {
        return empty($this->relations);
    }

    public function get($name): ?Relation
    {
        return $this->relations[$name] ?? null;
    }

    public function set($name, Relation $relation)
    {
        $this->relations[$name] = $relation;
    }

    public function remove($name)
    {
        unset($this->relations[$name]);
    }

    public function has($name): bool
    {
        return isset($this->relations[$name]);
    }

    public function offsetExists(mixed $offset): bool
    {
        return $this->has($offset);
    }

    public function offsetGet(mixed $offset): ?Relation
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
        return new \ArrayIterator($this->relations);
    }

}