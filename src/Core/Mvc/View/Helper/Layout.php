<?php

namespace App\Core\Mvc\View\Helper;

class Layout
{
    private $layout;
    public function __invoke($layout)
    {
        $this->setLayout($layout);
        return $this;
    }

    public function setLayout($layout)
    {
        $this->layout = $layout;
        return $this;
    }

    public function getLayout()
    {
        return $this->layout;
    }
}