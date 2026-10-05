<?php

namespace App\Core\Mvc\View;

use App\Core\Di\Locator;

class View
{
    private Renderer\RendererInterface $renderer;

    private Locator $helpers;

    public function setRenderer(Renderer\RendererInterface $renderer)
    {
        $this->renderer = $renderer;
        $this->renderer->setView($this);
    }

    public function getHelpers(): Locator
    {
        return $this->helpers;
    }

    public function setHelpers(Locator $helpers)
    {
        $this->helpers = $helpers;
    }

    public function render($template, $data = [])
    {
        try {
            if ($this->renderer) {
                $content = $this->renderer->render($template, $data);
                $layoutHelper = $this->getHelpers()->get('layout');
                if ($layoutHelper->hasLayout()) {
                    $layoutName = $layoutHelper->getLayout();
                    $content = $this->renderer->render('layouts/' . $layoutName);
                }
                $layoutHelper->reset();
                return $content;
            }
            return '';
        } catch (\Throwable $e) {
            return ($e->getMessage());
        }
    }

}