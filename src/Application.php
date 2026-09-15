<?php

namespace App;

use App\Core\Db\Exception\DbException;
use App\Core\Di;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

class Application
{
    private static $_instance;

    private $container;

    private function bootstrap()
    {
        $this->setupDependencyInjection();
        $this->setupConfig();

        $request = Request::createFromGlobals();
        
        $referer = $request->headers->get('Origin');

        if ($request->getMethod() === 'OPTIONS') {
            $response = new JsonResponse(null, 204);
            $this->withCors($response, $referer);
            $response->send();
            return;
        }

        $router = $this->container->get('router-matcher');
        $routerContext = $this->container->get('router-context');

        $routerContext->fromRequest($request);

        try {
            $routeParameters = $router->match($request->getPathInfo());
            $request->attributes->add($routeParameters);
        } catch (ResourceNotFoundException $e) {
            $response = new JsonResponse('Not Found', 404);
            $response->send();
            return;
        } catch (\Exception $e) {
            $response = new JsonResponse('An error occurred', 500);
            $response->send();
            return;
        }

        $controller = $routeParameters['_controller'][0];
        $action = $routeParameters['_controller'][1];

        $controllerInstance = new $controller();

        try {
            $controllerInstance->setContainer($this->container);
            $response = $controllerInstance->dispatch($action, $request);
        } catch (\Exception $e) {
            $this->container->get('watchdog')->error($e->getMessage());
            $response = new JsonResponse('An error occurred', 500);
            }
            
            if ($response instanceof Response) {
                $this->withCors($response, $referer);
                $response->send();
        }

        return;
    }

    private function withCors(Response $response, $referer = '*')
    {
        $response->headers->set('Access-Control-Allow-Origin', $referer);
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization');
    }

    private function setupDependencyInjection()
    {
        $definitions = require APP_DIR . '/config/services.php';

        $container = new Di\Container($definitions);
        $this->container = $container;
    }

    private function setupConfig()
    {
        $this->container->get('parameters')->load(CONFIG_DIR . '/parameters.' . APP_ENV . '.php');
    }

    public static function run()
    {
        if (self::$_instance === null) {
            self::$_instance = new self();
        }
        self::$_instance->bootstrap();
    }
}