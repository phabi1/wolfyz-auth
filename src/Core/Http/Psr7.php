<?php

namespace App\Core\Http;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Thin bridge to PSR-7, only used where league/oauth2-server requires it
 * (authorization & token endpoints). The rest of the app uses the plain
 * Request/Response classes above.
 */
class Psr7
{
    public static function requestFromGlobals(): ServerRequestInterface
    {
        $factory = new Psr17Factory();
        $creator = new ServerRequestCreator($factory, $factory, $factory, $factory);

        return $creator->fromGlobals();
    }

    public static function blankResponse(): ResponseInterface
    {
        return new \Nyholm\Psr7\Response();
    }

    public static function emit(ResponseInterface $response): void
    {
        http_response_code($response->getStatusCode());
        foreach ($response->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                header("{$name}: {$value}", false);
            }
        }
        echo $response->getBody();
    }
}
