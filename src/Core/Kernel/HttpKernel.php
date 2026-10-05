<?php
namespace App\Core\Kernel;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Http\JsonResponse;
use App\Core\Security\Firewall;

class HttpKernel extends Base
{
    public function run()
    {
        $this->bootstrap();

        try {
            $request = Request::fromGlobals();

            $referer = $request->headers->get('Origin');

            if ($request->method === 'OPTIONS') {
                $response = new Response('', 204);
                $this->withCors($response, $referer);
                $response->send();
                return;
            }

            $container = $this->getContainer();

            $routeMatcher = $container->get('route.matcher');

            $routeMatched = $routeMatcher->match($request);
            if (!$routeMatched) {
                throw new \Exception('Route not found');
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

        if (isset($response)) {
            $this->withCors($response, $referer);
        }
        $response?->send();

    }

    private function withCors($response, $referer)
    {
        $response->headers->set('Access-Control-Allow-Origin', $referer);
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Api-Key');
    }
}