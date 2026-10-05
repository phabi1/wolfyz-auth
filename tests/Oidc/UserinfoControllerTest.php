<?php

namespace App\Tests\Oidc;

use App\Core\Di\Container;
use App\Core\Http\JsonResponse;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Oidc\Controller\AuthController;
use App\Oidc\Userinfo;
use League\OAuth2\Server\Exception\OAuthServerException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionProperty;

class UserinfoControllerTest extends TestCase
{
    private array $server;
    private array $get;
    private array $post;

    protected function setUp(): void
    {
        $this->server = $_SERVER;
        $this->get = $_GET;
        $this->post = $_POST;
        $_SERVER = ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/oidc/userinfo'];
        $_GET = [];
        $_POST = [];
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->server;
        $_GET = $this->get;
        $_POST = $this->post;
    }

    #[DataProvider('methodProvider')]
    public function testReturnsJsonForGetAndPost(string $method): void
    {
        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer access-token';
        $userinfo = $this->createMock(Userinfo::class);
        $userinfo->expects(self::once())->method('build')
            ->with(self::callback(fn (ServerRequestInterface $request) =>
                $request->getMethod() === $method && $request->getHeaderLine('Authorization') === 'Bearer access-token'
            ))
            ->willReturn(['sub' => 'user-123', 'name' => 'Jane Doe']);

        $response = $this->controller($userinfo)->userinfoAction(Request::fromGlobals());

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(200, $this->responseStatus($response));
        self::assertSame(['sub' => 'user-123', 'name' => 'Jane Doe'], $this->body($response));
        self::assertSame('application/json', $response->headers->get('Content-Type'));
        self::assertSame('no-store', $response->headers->get('Cache-Control'));
    }

    public static function methodProvider(): array
    {
        return [['GET'], ['POST']];
    }

    #[DataProvider('errorProvider')]
    public function testReturnsBearerErrors(?string $header, string $error, int $status, string $challenge): void
    {
        if ($header !== null) {
            $_SERVER['HTTP_AUTHORIZATION'] = $header;
        }
        $userinfo = $this->createMock(Userinfo::class);
        $userinfo->method('build')->willThrowException(new OAuthServerException('Rejected.', 0, $error, $status));

        $response = $this->controller($userinfo)->userinfoAction(Request::fromGlobals());

        self::assertSame($status, $this->responseStatus($response));
        self::assertSame($error, $this->body($response)['error']);
        self::assertSame($challenge, $response->headers->get('WWW-Authenticate'));
        self::assertSame('no-store', $response->headers->get('Cache-Control'));
    }

    public static function errorProvider(): array
    {
        return [
            'no authentication' => [null, 'invalid_token', 401, 'Bearer'],
            'bad token' => ['Bearer bad', 'invalid_token', 401, 'Bearer error="invalid_token"'],
            'missing scope' => ['Bearer token', 'insufficient_scope', 403, 'Bearer error="insufficient_scope", scope="openid"'],
        ];
    }

    public function testRejectsUnsupportedMethodWithoutLookingUpUser(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'DELETE';
        $userinfo = $this->createMock(Userinfo::class);
        $userinfo->expects(self::never())->method('build');
        $response = $this->controller($userinfo)->userinfoAction(Request::fromGlobals());

        self::assertSame(405, $this->responseStatus($response));
        self::assertSame('GET, POST', $response->headers->get('Allow'));
    }

    public function testUserinfoIsRegisteredWithItsRoute(): void
    {
        $services = require __DIR__ . '/../../config/services/oidc.php';
        $routes = require __DIR__ . '/../../config/routes/oidc.php';
        self::assertSame(Userinfo::class, $services['oidc.userinfo']['class']);
        self::assertSame(['@oidc.server', '@oidc.server.repository.user'], $services['oidc.userinfo']['arguments']);
        self::assertSame('/oidc/userinfo', $routes['oidc-userinfo']['path']);
        self::assertSame('userinfo', $routes['oidc-userinfo']['action']);
        self::assertSame('oidc.auth', $routes['oidc-userinfo']['controller']);
    }

    private function controller(Userinfo $userinfo): AuthController
    {
        $container = new Container(['oidc.userinfo' => ['factory' => fn () => $userinfo]]);
        $controller = new AuthController();
        $controller->setContainer($container);
        return $controller;
    }

    private function responseStatus(Response $response): int
    {
        return (new ReflectionProperty(Response::class, 'status'))->getValue($response);
    }

    private function body(Response $response): array
    {
        return json_decode((new ReflectionProperty(Response::class, 'body'))->getValue($response), true, 512, JSON_THROW_ON_ERROR);
    }
}
