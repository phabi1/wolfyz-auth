<?php

namespace App\Core\Db;

class Expr implements DbAwareInterface
{
    use DbAwareTrait;

    private $types = [
        'eq' => Expr\Eq::class,
        'in' => Expr\In::class,
        'and' => Expr\AndX::class,
        'or' => Expr\OrX::class,
        'gt' => Expr\MoreThan::class,
        'lt' => Expr\LessThan::class,
        'like' => Expr\Like::class,
    ];

    public function __call($method, $args)
    {
        if (isset($this->types[$method])) {
            $class = $this->types[$method];
            $instance = new $class(...$args);
            if ($instance instanceof DbAwareInterface) {
                $instance->setDb($this->db);
            }
            return $instance;
        }
        throw new \Exception('Unknown expression type: ' . $method);
    }
}