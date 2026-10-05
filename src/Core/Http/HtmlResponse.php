<?php

namespace App\Core\Http;

class HtmlResponse extends Response
{
    public function __construct(string $html, int $status = 200, array $headers = [])
    {
        $headers['Content-Type'] = 'text/html; charset=utf-8';
        parent::__construct($html, $status, $headers);
    }
}