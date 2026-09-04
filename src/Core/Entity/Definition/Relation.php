<?php 

namespace App\Core\Entity\Definition;

class Relation implements \ArrayAccess
{
    const TYPE_ONE_TO_ONE = 'one_to_one';
    const TYPE_ONE_TO_MANY = 'one_to_many';

    private static $validTypes = [
        self::TYPE_ONE_TO_ONE,
        self::TYPE_ONE_TO_MANY
    ];

    protected $name;
    protected $type;
    protected $target_entity;
    protected $options;

    public function __construct($name, $type, $target_entity, $options = [])
    {
        if (!in_array($type, self::$validTypes)) {
            throw new \InvalidArgumentException("Invalid relation type: {$type}");
        }
        $this->name = $name;
        $this->type = $type;
        $this->target_entity = $target_entity;
        $this->options = $options;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getTargetEntity()
    {
        return $this->target_entity;
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function setOptions(array $options)
    {
        $this->options = $options;
    }

    public function getOption($name) {
        return $this->options[$name] ?? null;
    }

    public function setOption($name, $value)
    {
        $this->options[$name] = $value;
    }

    public function offsetExists(mixed $offset): bool
    {
        return property_exists($this, $offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        if (property_exists($this, $offset)) {
            return $this->$offset;
        }
        return $this->options[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (property_exists($this, $offset)) {
            $this->$offset = $value;
        } else {
            $this->options[$offset] = $value;
        }
    }

    public function offsetUnset(mixed $offset): void
    {
        if (property_exists($this, $offset)) {
            $this->$offset = null;
        } else {
            unset($this->options[$offset]);
        }
    }

    public static function factory($name, $data)
    {
        $type = $data['type'] ?? null;
        unset($data['type']);
        $targetEntity = $data['target_entity'] ?? null;
        unset($data['target_entity']);
        $options = $data['options'] ?? [];
        return new self($name, $type, $targetEntity, $options);
    }
}