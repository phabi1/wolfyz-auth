<?php

namespace App\Core\Db\Expr;

class Like implements ExprInterface
{
    private $field;
    private $value;

    function __construct($field = null, $value = null)
    {
        $this->field = $field;
        $this->value = $value;
    }

    function build()
    {
        return $this->field . ' LIKE "' . $this->value . '"';
    }
}