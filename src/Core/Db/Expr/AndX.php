<?php

namespace App\Core\Db\Expr;

class AndX extends Composite
{
    public function getOperator()
    {
        return 'AND';
    }
}