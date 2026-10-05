<?php

namespace App\Core\Routing\Route;

use App\Core\Http\Request;
use App\Core\Routing\RouteMatched;

interface RouteInterface
{
    public function getName(): string;

    public function match(Request $request): ?RouteMatched;

    public function assemble(array $parameters = []): string;
}