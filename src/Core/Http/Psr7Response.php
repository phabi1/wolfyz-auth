<?php

namespace App\Core\Http;

use Psr\Http\Message\ResponseInterface;

class Psr7Response extends Response
{
    public function __construct(private ResponseInterface $response)
    {
        parent::__construct((string) $response->getBody(), $response->getStatusCode());
        if ($response->hasHeader('Vary')) {
            $this->headers->set('Vary', $response->getHeaderLine('Vary'));
        }
    }

    public function send(): void
    {
        $response = $this->response;
        foreach ($this->headers->all() as $name => $value) {
            $response = $response->withHeader($name, $value);
        }
        Psr7::emit($response);
    }
}
