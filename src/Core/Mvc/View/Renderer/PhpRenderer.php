<?php

namespace App\Core\Mvc\View\Renderer;

use App\Core\Mvc\View\View;

class PhpRenderer implements RendererInterface
{
    private $view;

    public function setView(View $view)
    {
        $this->view = $view;
    }

    public function getHelpers()
    {
        return $this->view ? $this->view->getHelpers() : null;
    }

    public function getHelper($name)
    {
        $helpers = $this->getHelpers();
        return $helpers && $helpers->has($name) ? $helpers->get($name) : null;
    }

    public function render($template, $data = [])
    {
        $templatePath = $this->getTemplatePath($template);
        extract($data);
        ob_start();
        include $templatePath;
        $content = ob_get_clean();
        return $content;
    }

    private function getTemplatePath($template)
    {
        return APP_DIR . '/views/' . $template . '.php';
    }

    public function __call($method, $args)
    {
        $helper = $this->getHelper($method);
        if ($helper && is_callable($helper)) {
            return call_user_func_array($helper, $args);
        }
        return null;
    }

}