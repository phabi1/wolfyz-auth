<?php

namespace App\Core\Routing\Route;

class RouteFactory
{
    public static function create(string $name, array $info): RouteInterface
    {
        switch ($info['type']) {
            case 'literal':
                return new LiteralRoute($name, $info);
            case 'segment':
                return new SegmentRoute($name, $info);
            default:
                throw new \InvalidArgumentException('Unsupported route type: ' . $info['type']);
        }
    }
}