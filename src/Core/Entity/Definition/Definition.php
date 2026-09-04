<?php

namespace App\Core\Entity\Definition;

class Definition implements \ArrayAccess
{
    private $name;

    private $table = '';

    private $repository;

    private Fields $fields;

    private $relations = [];

    private $search = null;

    public function __construct(array $data = [])
    {
        $this->fields = new Fields();
        $this->relations = new Relations();
        foreach ($data as $key => $value) {
            if ($key === 'fields' && is_array($value)) {
                foreach ($value as $fieldName => $field) {
                    $field = Field::factory($fieldName, $field);
                    $this->fields->set($fieldName, $field);
                }
            } else if ($key === 'relations' && is_array($value)) {
                foreach ($value as $relationName => $relation) {
                    $relation = Relation::factory($relationName, $relation);
                    $this->relations->set($relationName, $relation);
                }
            } else if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    public function getName()
    {
        return $this->name;
    }

    public function getTable()
    {
        return $this->table;
    }

    public function getRepository()
    {
        return $this->repository;
    }

    public function getFields(): Fields
    {
        return $this->fields;
    }

    public function getRelations(): Relations
    {
        return $this->relations;
    }

    public function hasRelations(): bool
    {
        return !$this->relations->isEmpty();
    }

    public function hasRelation(string $name): bool
    {
        return $this->relations->has($name);
    }

    public function getRelation(string $name)
    {
        return $this->relations->get($name);
    }

    public function getSearch() {
        return $this->search;
    }

    public function offsetExists($offset): bool
    {
        return property_exists($this, $offset);
    }

    public function offsetGet($offset): mixed
    {
        return $this->$offset;
    }

    public function offsetSet($offset, $value): void
    {
        if (property_exists($this, $offset)) {
            $this->$offset = $value;
        }
    }

    public function offsetUnset($offset): void
    {
        if (property_exists($this, $offset)) {
            $this->$offset = null;
        }
    }
}