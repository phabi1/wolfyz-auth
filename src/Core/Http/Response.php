<?php

namespace App\Core\Http;

use App\Core\Lib\Bag;

class Response
{
    public readonly Bag $headers;

    public function __construct(
        protected string $body = '',
        protected int $status = 200,
        array $headers = [],
    ) {
        $this->headers = new Bag($headers);
    }

    public function send(): void
    {
        http_response_code($this->status);
        foreach ($this->headers->all() as $name => $value) {
            header("{$name}: {$value}");
        }
        echo $this->body;
    }
}
