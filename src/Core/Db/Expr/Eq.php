<?php

namespace App\Core\Db\Expr;

use App\Core\Db\DbAwareInterface;
use App\Core\Db\DbAwareTrait;
class Eq implements ExprInterface, DbAwareInterface
{
    use DbAwareTrait;

    private $field;
    private $value;

    function __construct($field = null, $value = null)
    {
        $this->field = $field;
        $this->value = $value;
    }

    function build()
    {
        return $this->field . ' = ' . $this->db->escape($this->value);
    }
}