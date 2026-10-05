<?php

namespace App\Core\Routing\Route;

use App\Core\Http\Request;
use App\Core\Routing\Route\Route;
use App\Core\Routing\RouteMatched;

class LiteralRoute extends Route
{

    private $path;

    public function __construct(string $name, array $options = []) {
        parent::__construct($name, $options);
        $this->path = $options['path'] ?? '';
    }

    public function setPath(string $path): void
    {
        $this->path = $path;
    }
    public function getPath(): string
    {
        return $this->path;
    }

    public function match(Request $request): RouteMatched | null
    {
        if ($request->path === $this->path) {
            return new RouteMatched($this->getName(), $this->getController(), $this->getAction());
        }
        return null;
    }

    public function assemble(array $parameters = []): string
    {
        return $this->path;
    }
}