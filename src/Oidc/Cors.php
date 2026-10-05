<?php

namespace App\Oidc;

use App\Core\Config\Parameters;
use App\Core\Http\JsonResponse;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Routing\RouteMatched;

class Cors
{
    private const METHODS = [
        'authorize' => ['GET'],
        'token' => ['POST'],
        'userinfo' => ['GET', 'POST'],
        'introspect' => ['GET'],
        'jwks' => ['GET'],
    ];
    private const HEADERS = ['content-type', 'authorization'];
    private array $origins;

    public function __construct(Parameters $parameters)
    {
        $origins = $parameters->get('oidc.cors.allowed_origins', []);
        if (!is_array($origins)) {
            throw new \InvalidArgumentException('OIDC CORS allowed_origins must be an array.');
        }
        foreach ($origins as $origin) {
            if (!is_string($origin)
                || !preg_match('~^https?://[^/?#@\s]+$~D', $origin)
                || !filter_var($origin, FILTER_VALIDATE_URL)
            ) {
                throw new \InvalidArgumentException('OIDC CORS origins must be exact HTTP(S) origins without paths.');
            }
        }
        $this->origins = $origins;
    }

    public function supports(?RouteMatched $route): bool
    {
        return $route !== null && $route->controller === 'oidc.auth' && isset(self::METHODS[$route->action]);
    }

    public function preflight(Request $request, RouteMatched $route): Response
    {
        $methods = self::METHODS[$route->action];
        $origin = $request->header('Origin');
        if ($origin === null) {
            return new Response('', 204, ['Allow' => implode(', ', [...$methods, 'OPTIONS'])]);
        }
        $method = $request->header('Access-Control-Request-Method');
        $headers = $request->header('Access-Control-Request-Headers');
        $requestedHeaders = $headers === null || trim($headers) === '' ? []
            : array_map(static fn (string $header): string => strtolower(trim($header)), explode(',', $headers));
        if (!in_array($origin, $this->origins, true)
            || !in_array($method, $methods, true)
            || array_diff($requestedHeaders, self::HEADERS) !== []
        ) {
            return new JsonResponse(
                ['error' => 'cors_request_denied', 'message' => 'Origin, method or headers are not allowed.'],
                403,
                ['Vary' => 'Origin, Access-Control-Request-Method, Access-Control-Request-Headers'],
            );
        }
        $response = new Response('', 204, [
            'Access-Control-Allow-Methods' => implode(', ', $methods),
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization',
            'Access-Control-Max-Age' => '600',
            'Vary' => 'Origin, Access-Control-Request-Method, Access-Control-Request-Headers',
        ]);
        return $this->apply($request, $response);
    }

    public function apply(Request $request, Response $response): Response
    {
        $vary = array_filter(array_map('trim', explode(',', $response->headers->get('Vary') ?? '')));
        if (!in_array('origin', array_map('strtolower', $vary), true) && !in_array('*', $vary, true)) {
            $vary[] = 'Origin';
        }
        $response->headers->set('Vary', implode(', ', $vary));
        $origin = $request->header('Origin');
        if ($origin !== null && in_array($origin, $this->origins, true)) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
            $response->headers->set('Access-Control-Expose-Headers', 'WWW-Authenticate');
        }
        return $response;
    }
}
