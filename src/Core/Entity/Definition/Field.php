<?php

namespace App\Core\Entity\Definition;

class Field
{
    const TYPE_STRING = 'string';
    const TYPE_INTEGER = 'integer';
    const TYPE_BOOLEAN = 'boolean';
    const TYPE_ARRAY = 'array';
    const TYPE_JSON = 'json';
    const TYPE_DATETIME = 'datetime';
    const TYPE_DATE = 'date';
    const TYPE_TIME = 'time';

    private static $validTypes = [
        self::TYPE_STRING,
        self::TYPE_INTEGER,
        self::TYPE_BOOLEAN,
        self::TYPE_ARRAY,
        self::TYPE_JSON,
        self::TYPE_DATETIME,
        self::TYPE_DATE,
        self::TYPE_TIME
    ];

    protected $name;
    protected $type;
    protected $options;

    public function __construct($name, $type, $options = [])
    {
        if (!in_array($type, self::$validTypes)) {
            throw new \InvalidArgumentException("Invalid field type: {$type}");
        }
        $this->name = $name;
        $this->type = $type;
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

    public function getOptions()
    {
        return $this->options;
    }
}