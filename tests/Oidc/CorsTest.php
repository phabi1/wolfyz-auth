<?php

namespace App\Tests\Oidc;

use App\Core\Config\Parameters;
use App\Core\Di\Container;
use App\Core\Http\Psr7Response;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Kernel\HttpKernel;
use App\Core\Routing\RouteMatched;
use App\Core\Routing\RouteMatcher;
use App\Oidc\Controller\AuthController;
use App\Oidc\Cors;
use App\Oidc\Server\Server;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Exception\OAuthServerException;
use Nyholm\Psr7\Response as PsrResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

class CorsTest extends TestCase
{
    private array $globals;
    private Cors $cors;

    protected function setUp(): void
    {
        $this->globals = [$_SERVER, $_GET, $_POST];
        $this->cors = new Cors(new Parameters([
            'oidc' => ['cors' => ['allowed_origins' => ['http://localhost:4200', 'https://club.example.com']]],
        ]));
    }

    protected function tearDown(): void
    {
        [$_SERVER, $_GET, $_POST] = $this->globals;
    }

    private function request(string $method = 'GET', array $headers = [], string $path = '/oidc/token'): Request
    {
        $_SERVER = ['REQUEST_METHOD' => $method, 'REQUEST_URI' => $path, 'HTTP_HOST' => 'auth.example.com'];
        foreach ($headers as $name => $value) {
            $_SERVER['HTTP_' . strtoupper(str_replace('-', '_', $name))] = $value;
        }
        $_GET = $_POST = [];
        return Request::fromGlobals();
    }

    private function responseStatus(Response $response): int
    {
        return (new ReflectionProperty(Response::class, 'status'))->getValue($response);
    }

    private function route(string $action): RouteMatched
    {
        return new RouteMatched('oidc-' . $action, 'oidc.auth', $action);
    }

    public static function endpoints(): array
    {
        return [
            ['authorize', 'GET'], ['token', 'POST'], ['userinfo', 'GET'],
            ['userinfo', 'POST'], ['introspect', 'GET'], ['jwks', 'GET'],
        ];
    }

    #[DataProvider('endpoints')]
    public function testPreflightAllowsOnlyEndpointMethodsAndHeaders(string $action, string $method): void
    {
        $response = $this->cors->preflight($this->request('OPTIONS', [
            'Origin' => 'http://localhost:4200',
            'Access-Control-Request-Method' => $method,
            'Access-Control-Request-Headers' => 'Content-Type, AUTHORIZATION',
        ]), $this->route($action));
        self::assertSame(204, $this->responseStatus($response));
        self::assertSame('http://localhost:4200', $response->headers->get('Access-Control-Allow-Origin'));
        self::assertSame($action === 'userinfo' ? 'GET, POST' : $method, $response->headers->get('Access-Control-Allow-Methods'));
        self::assertSame('Content-Type, Authorization', $response->headers->get('Access-Control-Allow-Headers'));
        self::assertSame('600', $response->headers->get('Access-Control-Max-Age'));
        self::assertStringContainsString('Access-Control-Request-Headers', $response->headers->get('Vary'));
        self::assertNull($response->headers->get('Access-Control-Allow-Credentials'));
    }

    public static function deniedPreflights(): array
    {
        return [
            ['https://attacker.example.com', 'POST', 'Content-Type'],
            ['http://localhost:4200.attacker.example.com', 'POST', 'Content-Type'],
            ['null', 'POST', 'Content-Type'],
            ['http://localhost:4200', 'DELETE', 'Content-Type'],
            ['http://localhost:4200', 'POST', 'X-Api-Key'],
            ['http://localhost:4200', null, 'Content-Type'],
        ];
    }

    #[DataProvider('deniedPreflights')]
    public function testRejectedPreflightDoesNotGrantCors(string $origin, ?string $method, string $header): void
    {
        $headers = ['Origin' => $origin, 'Access-Control-Request-Headers' => $header];
        if ($method !== null) {
            $headers['Access-Control-Request-Method'] = $method;
        }
        $response = $this->cors->preflight($this->request('OPTIONS', $headers), $this->route('token'));
        self::assertSame(403, $this->responseStatus($response));
        self::assertNull($response->headers->get('Access-Control-Allow-Origin'));
    }

    public function testActualErrorsAndRedirectsReceiveCorsAndPreserveVary(): void
    {
        foreach ([200, 302, 400, 401, 403, 500] as $status) {
            $response = $this->cors->apply(
                $this->request(headers: ['Origin' => 'https://club.example.com']),
                new Response('body', $status, ['Vary' => 'Accept-Encoding', 'WWW-Authenticate' => 'Bearer']),
            );
            self::assertSame($status, $this->responseStatus($response));
            self::assertSame('https://club.example.com', $response->headers->get('Access-Control-Allow-Origin'));
            self::assertSame('Accept-Encoding, Origin', $response->headers->get('Vary'));
            self::assertSame('WWW-Authenticate', $response->headers->get('Access-Control-Expose-Headers'));
        }
    }

    public function testMissingOrUnapprovedOriginNeverGetsCorsPermission(): void
    {
        foreach ([[], ['Origin' => 'https://denied.example.com']] as $headers) {
            $response = $this->cors->apply($this->request(headers: $headers), new Response('body'));
            self::assertNull($response->headers->get('Access-Control-Allow-Origin'));
            self::assertSame('Origin', $response->headers->get('Vary'));
        }
    }

    public function testNonOidcRoutesAreExcluded(): void
    {
        self::assertFalse($this->cors->supports(new RouteMatched('signin', 'auth.sign', 'signin')));
        self::assertFalse($this->cors->supports(null));
        foreach (require dirname(__DIR__, 2) . '/config/routes/oidc.php' as $name => $route) {
            self::assertTrue($this->cors->supports(new RouteMatched($name, $route['controller'], $route['action'])));
        }
    }

    private function kernel(RouteMatched $route, object $controller, ?object $watchdog = null): HttpKernel
    {
        $matcher = $this->createMock(RouteMatcher::class);
        $matcher->method('match')->willReturn($route);
        $locator = new class($controller) {
            public function __construct(private object $controller) {}
            public function get(string $id): object { return $this->controller; }
        };
        $parameters = new Parameters(['oidc' => ['cors' => ['allowed_origins' => ['http://localhost:4200']]]]);
        $container = new Container([
            'route.matcher' => ['factory' => fn () => $matcher],
            'controller' => ['factory' => fn () => $locator],
            'parameters' => ['factory' => fn () => $parameters],
            'watchdog' => ['factory' => fn () => $watchdog],
        ]);
        return new class($container) extends HttpKernel {
            public function __construct(private Container $testContainer) {}
            public function getContainer() { return $this->testContainer; }
        };
    }

    public function testKernelPreflightDoesNotDispatchController(): void
    {
        $controller = new class {
            public function dispatch(): Response { throw new \LogicException('Must not be dispatched.'); }
        };
        $kernel = $this->kernel($this->route('token'), $controller);
        $response = $kernel->handle($this->request('OPTIONS', [
            'Origin' => 'http://localhost:4200', 'Access-Control-Request-Method' => 'POST',
        ]));
        self::assertSame(204, $this->responseStatus($response));
    }

    public function testKernelAddsCorsToInternalErrors(): void
    {
        $controller = new class {
            public function dispatch(): Response { throw new \RuntimeException('Test failure.'); }
        };
        $watchdog = new class {
            public bool $logged = false;
            public function error(string $message, array $context): void { $this->logged = true; }
        };
        $response = $this->kernel($this->route('token'), $controller, $watchdog)->handle(
            $this->request(headers: ['Origin' => 'http://localhost:4200']),
        );
        self::assertTrue($watchdog->logged);
        self::assertSame(500, $this->responseStatus($response));
        self::assertSame('http://localhost:4200', $response->headers->get('Access-Control-Allow-Origin'));
    }

    public function testKernelDoesNotAddCorsToSignIn(): void
    {
        $controller = new class {
            public function dispatch(): Response { return new Response('signin'); }
        };
        $response = $this->kernel(new RouteMatched('signin', 'auth.sign', 'signin'), $controller)->handle(
            $this->request(headers: ['Origin' => 'http://localhost:4200'], path: '/signin'),
        );
        self::assertNull($response->headers->get('Access-Control-Allow-Origin'));
    }

    public static function tokenResponses(): array
    {
        return [[false], [true]];
    }

    #[DataProvider('tokenResponses')]
    public function testTokenResponsesReturnThroughKernelInsteadOfEmittingEarly(bool $error): void
    {
        $authorization = $this->createMock(AuthorizationServer::class);
        $method = $authorization->method('respondToAccessTokenRequest');
        if ($error) {
            $method->willThrowException(new OAuthServerException('Rejected.', 0, 'invalid_grant', 400));
        } else {
            $method->willReturn(new PsrResponse(200, ['Content-Type' => 'application/json'], '{"access_token":"test"}'));
        }
        $server = $this->createMock(Server::class);
        $server->method('authorizationServer')->willReturn($authorization);
        $controller = new AuthController();
        $controller->setContainer(new Container(['oidc.server' => ['factory' => fn () => $server]]));
        $request = $this->request('POST', ['Origin' => 'http://localhost:4200']);
        ob_start();
        try {
            $response = $this->kernel($this->route('token'), $controller)->handle($request);
            self::assertSame('', ob_get_contents());
        } finally {
            ob_end_clean();
        }
        self::assertInstanceOf(Psr7Response::class, $response);
        self::assertSame($error ? 400 : 200, $this->responseStatus($response));
        self::assertSame('http://localhost:4200', $response->headers->get('Access-Control-Allow-Origin'));
        ob_start();
        try {
            $response->send();
            $body = json_decode(ob_get_contents(), true, 512, JSON_THROW_ON_ERROR);
            self::assertSame($error ? 'invalid_grant' : 'test', $body[$error ? 'error' : 'access_token']);
        } finally {
            ob_end_clean();
        }
    }

    public function testInvalidOriginConfigurationFailsExplicitly(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Cors(new Parameters(['oidc' => ['cors' => ['allowed_origins' => ['*']]]]));
    }

    public function testPsrResponsePreservesExistingVaryAndResponseBody(): void
    {
        $response = new Psr7Response(new PsrResponse(401, [
            'Vary' => 'Accept-Encoding', 'WWW-Authenticate' => 'Bearer',
        ], 'error'));
        $this->cors->apply($this->request(headers: ['Origin' => 'http://localhost:4200']), $response);
        self::assertSame('Accept-Encoding, Origin', $response->headers->get('Vary'));
        ob_start();
        try {
            $response->send();
            self::assertSame('error', ob_get_contents());
        } finally {
            ob_end_clean();
        }
    }
}
