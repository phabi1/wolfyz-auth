<?php

namespace App\Core\Mvc\View;

use App\Core\Mvc\View\Helpers;

use App\Core\Mvc\View\Renderer\PhpRenderer;

class ViewFactory
{
    public static function create(Helpers $helpers): View
    {
        $view = new View();
        $view->setHelpers($helpers);
        $view->setRenderer(new PhpRenderer());
        return $view;
    }
}