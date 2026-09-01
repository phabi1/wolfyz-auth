<?php

namespace App\Core\Db\Expr;

class OrX extends Composite
{
    public function getOperator()
    {
        return 'OR';
    }
}