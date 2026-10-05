<?php

namespace App\Core\Routing\Route;

use App\Core\Http\Request;
use App\Core\Routing\RouteMatched;
use App\Core\Routing\Route\RouteInterface;

abstract class Route implements RouteInterface
{
    private $name;

    private $controller;
    private $action;

    public function __construct(string $name, array $options = [])
    {
        $this->name = $name;
        $this->controller = $options['controller'] ?? '';
        $this->action = $options['action'] ?? '';
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getController(): string
    {
        return $this->controller;
    }

    public function setController(string $controller): void
    {
        $this->controller = $controller;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function setAction(string $action): void
    {
        $this->action = $action;
    }

    abstract public function match(Request $request): ?RouteMatched;

    abstract public function assemble(array $parameters = []): string;
}