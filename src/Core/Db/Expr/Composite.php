<?php

namespace App\Core\Db\Expr;

use App\Core\Db\Expr\ExprInterface;

abstract class Composite implements ExprInterface
{
    private $_parts = [];

    public function __construct($parts = [])
    {
        foreach ($parts as $part) {
            $this->add($part);
        }
    }

    public function add(ExprInterface $expr)
    {
        $this->_parts[] = $expr;
        return $this;
    }

    public function getParts()
    {
        return $this->_parts;
    }

    public function build()
    {
        $parts = array_map(function ($part) {
            return '(' . $part->build() . ')';
        }, $this->getParts());

        return implode(' ' . $this->getOperator() . ' ', $parts);
    }

    abstract public function getOperator();
}