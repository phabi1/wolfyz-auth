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
        foreach ($this->headers->all() as $name => $value) {
            header("{$name}: {$value}");
        }
        // After the headers: PHP forces 401 when WWW-Authenticate is sent
        // (and 302 for Location), which would override e.g. a 403.
        http_response_code($this->status);
        echo $this->body;
    }
}
