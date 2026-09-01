<?php 

namespace App\Core\Entity\Definition;

class Relation
{
    const TYPE_ONE_TO_ONE = 'one_to_one';
    const TYPE_ONE_TO_MANY = 'one_to_many';

    private static $validTypes = [
        self::TYPE_ONE_TO_ONE,
        self::TYPE_ONE_TO_MANY
    ];

    protected $name;
    protected $type;
    protected $targetEntity;
    protected $options;

    public function __construct($name, $type, $targetEntity, $options = [])
    {
        if (!in_array($type, self::$validTypes)) {
            throw new \InvalidArgumentException("Invalid relation type: {$type}");
        }
        $this->name = $name;
        $this->type = $type;
        $this->targetEntity = $targetEntity;
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
        return $this->targetEntity;
    }

    public function getOptions()
    {
        return $this->options;
    }
}