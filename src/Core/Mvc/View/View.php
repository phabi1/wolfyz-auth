<?php

namespace App\Core\Mvc\View;

class View
{
    private Renderer\RendererInterface $renderer;

    private $helpers;

    public function setRenderer(Renderer\RendererInterface $renderer)
    {
        $this->renderer = $renderer;
        $this->renderer->setView($this);
    }

    public function getHelpers()
    {
        return $this->helpers;
    }

    public function setHelpers($helpers)
    {
        $this->helpers = $helpers;
    }

    public function render($template, $data = [])
    {
        try {
        if ($this->renderer) {
            $content = $this->renderer->render($template, $data);
            if ($layoutHelper = $this->getHelpers()->get('layout')) {
                $layoutName = $layoutHelper->getLayout();
                if ($layoutName) {
                    $content = $this->renderer->render('layouts/' . $layoutName, ['content' => $content]);
                }
            }

            return $content;
        }
        return '';
        } catch (\Throwable $e) {
            return ($e->getMessage());
        }
    }

    private function renderView($template, $data = [])
    {
        return $this->renderer ? $this->renderer->render($template, $data) : '';
    }
}