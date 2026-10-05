<?php

namespace App\Core\Http;

class RedirectResponse extends Response
{
    public function __construct(string $url, int $status = 302)
    {
        parent::__construct('', $status, ['Location' => $url]);
    }
}
