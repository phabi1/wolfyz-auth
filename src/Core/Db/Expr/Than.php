<?php

namespace App\Core\Db\Expr;

abstract class Than implements ExprInterface
{

    protected $parts;

    protected $equals = false;


    public function __construct($parts, $equals = false)
    {
        $this->parts = $parts;
        $this->equals = $equals;
    }

    public function build()
    {
        return $this->parts[0] . ' ' . $this->getOperator($this->equals) . ' ' . $this->parts[1];
    }

    protected abstract function getOperator(bool $equals): string;

}