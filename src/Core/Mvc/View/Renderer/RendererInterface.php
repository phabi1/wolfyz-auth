<?php

namespace App\Core\Mvc\View\Renderer;

use App\Core\Mvc\View\View;

interface RendererInterface
{
    public function setView(View $view);
    public function render($template, $data = []);
}