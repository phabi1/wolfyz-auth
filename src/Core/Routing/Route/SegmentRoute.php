<?php

namespace App\Core\Routing\Route;

use App\Core\Http\Request;
use App\Core\Routing\RouteMatched;
use App\Core\Routing\Route\Route;

class SegmentRoute extends Route
{
    private $path;

    private $constraints = [];

    private $parameters = [];


    public function __construct(string $name, array $options = [])
    {
        parent::__construct($name, $options);
        $this->path = $options['path'] ?? '';
        $this->constraints = $options['constraints'] ?? [];
    }

    public function setPath(string $path): void
    {
        $this->path = $path;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function match(Request $request): RouteMatched|null
    {
        $pattern = preg_replace_callback('/\{(\w+)\}/', function ($matches) {
            $name = $matches[1];
            return $this->constraints[$name] ?? '[^/]+';
        }, $this->path);
        $pattern = '#^' . $pattern . '$#';
        if (preg_match($pattern, $request->path, $matches)) {
            $this->parameters = $matches;
            return new RouteMatched($this->getName(), $this->getController(), $this->getAction(), $this->parameters);
        }
        return null;
    }

    public function assemble(array $parameters = []): string
    {
        return preg_replace_callback('/\{(\w+)\}/', function ($matches) use ($parameters) {
            $name = $matches[1];
            return $parameters[$name] ?? '';
        }, $this->path);
    }
}