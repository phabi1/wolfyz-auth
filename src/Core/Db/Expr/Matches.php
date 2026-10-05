<?php

namespace App\Core\Db\Expr;

use App\Core\Db\DbAwareInterface;
use App\Core\Db\DbAwareTrait;

class Matches implements ExprInterface, DbAwareInterface
{
    use DbAwareTrait;
    
    private $fields;
    private $value;

    function __construct($fields = [], $value = [])
    {
        $this->fields = $fields;
        $this->value = $value;
    }

    function build()
    {
        return 'MATCH(' . implode(', ', $this->fields) . ') AGAINST (' . $this->db->escape($this->value) . ' IN BOOLEAN MODE)';
    }
}