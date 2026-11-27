<?php
namespace App\Core\Routing;

class RouteNotFoundException extends \Exception
{
    public function __construct(
        public readonly string $method,
        public readonly string $path,
    ) {
        parent::__construct('Route not found');
    }
}
