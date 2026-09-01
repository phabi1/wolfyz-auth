<?php

namespace App\Core\Mvc\Controller;

use App\Core\Di\ContainerAwareInterface;
use App\Core\Di\ContainerAwareTrait;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractController implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    protected function getService($key)
    {
        return $this->container->get($key);
    }

    public function dispatch($action, $request)
    {
        $method = $action . 'Action';
        if (method_exists($this, $method)) {
            return call_user_func_array([$this, $method], [$request]);
        } else {
            throw new \BadMethodCallException("Method {$method} does not exist in " . get_class($this));
        }
    }

    protected function render($template, $data = [])
    {
        $view = $this->getService('view');
        $content = $view->render($template, $data);
        return new Response($content);
    }

    protected function redirectToRoute($route, $parameters = [])
    {
        $router = $this->getService('router-generator');
        $url = $router->generate($route, $parameters);
        return new RedirectResponse($url);
    }
}