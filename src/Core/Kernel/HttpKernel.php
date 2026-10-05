<?php
namespace App\Core\Kernel;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Http\JsonResponse;
use App\Oidc\Cors;

class HttpKernel extends Base
{
    public function run()
    {
        $this->bootstrap();
        $this->handle(Request::fromGlobals())->send();
    }

    public function handle(Request $request): Response
    {
        $cors = null;
        $oidcRoute = false;
        try {
            $container = $this->getContainer();

            $routeMatcher = $container->get('route.matcher');

            $routeMatched = $routeMatcher->match($request);
            if (!$routeMatched) {
                throw new \Exception('Route not found');
            }

            $cors = new Cors($container->get('parameters'));
            $oidcRoute = $cors->supports($routeMatched);
            if ($oidcRoute && $request->method === 'OPTIONS') {
                return $cors->preflight($request, $routeMatched);
            }

            $controllers = $container->get('controller');
            $controllerId = $routeMatched->controller;

            $controller = $controllers->get($controllerId) ?? null;
            if (!$controller) {
                throw new \Exception('Controller not found');
            }
            $response = $controller->dispatch($routeMatched->action, $request);

        } catch (\Throwable $e) {
            $this->getContainer()->get('watchdog')->error($e->getMessage(), ['trace' => $e->getTraceAsString()]);
            $response = new JsonResponse(['error' => 'internal_server_error', 'message' => $e->getMessage()], 500);
        }

        if (!$response instanceof Response) {
            throw new \RuntimeException('Controller must return an HTTP response.');
        }
        return $oidcRoute ? $cors->apply($request, $response) : $response;
    }
}