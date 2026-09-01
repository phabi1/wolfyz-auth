<?php

namespace App\Core\Db\Expr;

class LessThan extends Than
{
    protected function getOperator(bool $equals): string
    {
        return $equals ? '<=' : '<';
    }
}