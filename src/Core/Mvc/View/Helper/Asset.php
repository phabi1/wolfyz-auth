<?php

namespace App\Core\Mvc\View\Helper;

class Asset
{
    private $mediaServer = '';

    public function __construct($mediaServer = null)
    {
        $this->mediaServer = $mediaServer;
    }

    public function __invoke($path)
    {
        return $this->build($path);
    }

    public function build($path)
    {
        return $this->mediaServer . '/' . ltrim($path, '/');
    }
}