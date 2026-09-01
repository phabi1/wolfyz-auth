<?php

namespace App\Core\Db\Expr;

class NotLike implements ExprInterface
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
        return $this->field . ' NOT LIKE "' . $this->value . '"';
    }
}