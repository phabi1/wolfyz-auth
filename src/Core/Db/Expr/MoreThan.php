<?php

namespace App\Core\Db\Expr;

class MoreThan extends Than
{
    protected function getOperator(bool $equals): string
    {
        return $equals ? '>=' : '>';
    }
}