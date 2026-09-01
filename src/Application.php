<?php

namespace App;

use App\Core\Di;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;

class Application
{
    private static $_instance;

    private $container;

    private function bootstrap()
    {
        $this->setupDependencyInjection();
        $this->setupConfig();

        $request = Request::createFromGlobals();

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

        $controller = $routeParameters[0];
        $action = $routeParameters[1];

        $controllerInstance = new $controller();

        $controllerInstance->setContainer($this->container);
        $response = $controllerInstance->dispatch($action, $request);

        if ($response instanceof Response) {
            $response->send();
        }

        return;
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