<?php

namespace App\Tests\User;

use App\Core\Config\Parameters;
use App\Core\Di\Container;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Routing\Route\RouteFactory;
use App\Core\Routing\RouteMatched;
use App\Oidc\Cors;
use App\User\Admin\UserAdminService;
use App\User\Api\UserApiGuard;
use App\User\Controller\UserApiController;
use App\User\Exception\UserNotFoundException;
use App\User\Exception\UserOperationForbiddenException;
use App\User\Exception\UserValidationException;
use League\OAuth2\Server\Exception\OAuthServerException;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

class UserApiControllerTest extends TestCase
{
    private array $globals;

    protected function setUp(): void
    {
        $this->globals = [$_SERVER, $_GET, $_POST];
    }

    protected function tearDown(): void
    {
        [$_SERVER, $_GET, $_POST] = $this->globals;
    }

    private function request(string $method, string $path, array $query = [], ?string $id = null): Request
    {
        $_SERVER = ['REQUEST_METHOD' => $method, 'REQUEST_URI' => $path, 'HTTP_AUTHORIZATION' => 'Bearer token'];
        $_GET = $query;
        $_POST = [];
        $request = Request::fromGlobals();
        if ($id !== null) {
            $request->attributes->set('id', $id);
        }
        return $request;
    }

    private function controller(UserAdminService $service, ?\Throwable $guardError = null): UserApiController
    {
        $guard = $this->createMock(UserApiGuard::class);
        if ($guardError) {
            $guard->method('authorize')->willThrowException($guardError);
        } else {
            $guard->method('authorize')->with(self::anything(), ['users.manage'])->willReturn('admin-id');
        }
        $controller = new UserApiController();
        $controller->setContainer(new Container([
            'user.admin' => ['factory' => fn () => $service],
            'user.api.guard' => ['factory' => fn () => $guard],
        ]));
        return $controller;
    }

    private function httpStatus(Response $response): int
    {
        return (new ReflectionProperty(Response::class, 'status'))->getValue($response);
    }

    private function body(Response $response): ?array
    {
        return json_decode((new ReflectionProperty(Response::class, 'body'))->getValue($response), true);
    }

    public function testListsUsers(): void
    {
        $service = $this->createMock(UserAdminService::class);
        $service->expects(self::once())->method('list')->with(['search' => 'jane'])
            ->willReturn(['items' => [], 'total' => 0, 'page' => 1, 'size' => 10]);

        $response = $this->controller($service)->collectionAction($this->request('GET', '/api/users', ['search' => 'jane']));

        self::assertSame(200, $this->httpStatus($response));
        self::assertSame(0, $this->body($response)['total']);
        self::assertSame('no-store', $response->headers->get('Cache-Control'));
    }

    public function testCreateReturns201(): void
    {
        $service = $this->createMock(UserAdminService::class);
        $service->method('create')->willReturn(['id' => 'u1']);

        $response = $this->controller($service)->collectionAction($this->request('POST', '/api/users'));

        self::assertSame(201, $this->httpStatus($response));
    }

    public function testItemActionsPassTheRouteIdAndTheCaller(): void
    {
        $service = $this->createMock(UserAdminService::class);
        $service->expects(self::once())->method('update')->with('u1', [], 'admin-id')->willReturn(['id' => 'u1']);
        $service->expects(self::once())->method('delete')->with('u1', 'admin-id');

        $controller = $this->controller($service);
        self::assertSame(200, $this->httpStatus($controller->itemAction($this->request('PATCH', '/api/users/u1', [], 'u1'))));
        self::assertSame(204, $this->httpStatus($controller->itemAction($this->request('DELETE', '/api/users/u1', [], 'u1'))));
    }

    public function testMapsDomainErrors(): void
    {
        $service = $this->createMock(UserAdminService::class);
        $service->method('get')->willThrowException(new UserNotFoundException('u1'));
        $service->method('update')->willThrowException(new UserValidationException(['email' => 'Invalid email address.']));
        $service->method('delete')->willThrowException(new UserOperationForbiddenException('no'));
        $controller = $this->controller($service);

        self::assertSame(404, $this->httpStatus($controller->itemAction($this->request('GET', '/api/users/u1', [], 'u1'))));
        $response = $controller->itemAction($this->request('PUT', '/api/users/u1', [], 'u1'));
        self::assertSame(422, $this->httpStatus($response));
        self::assertSame(['email' => 'Invalid email address.'], $this->body($response)['errors']);
        self::assertSame(403, $this->httpStatus($controller->itemAction($this->request('DELETE', '/api/users/u1', [], 'u1'))));
    }

    public function testRejectsUnauthorizedCallersBeforeTouchingUsers(): void
    {
        $service = $this->createMock(UserAdminService::class);
        $service->expects(self::never())->method('list');
        $error = new OAuthServerException('Administrator rights are required.', 0, 'access_denied', 403);

        $response = $this->controller($service, $error)->collectionAction($this->request('GET', '/api/users'));

        self::assertSame(403, $this->httpStatus($response));
        self::assertSame('access_denied', $this->body($response)['error']);
        self::assertSame('Bearer error="access_denied"', $response->headers->get('WWW-Authenticate'));
    }

    public function testRejectsUnsupportedMethods(): void
    {
        $service = $this->createMock(UserAdminService::class);
        $response = $this->controller($service)->collectionAction($this->request('DELETE', '/api/users'));

        self::assertSame(405, $this->httpStatus($response));
        self::assertSame('GET, POST', $response->headers->get('Allow'));
    }

    public function testItemRouteExposesTheId(): void
    {
        $routes = require dirname(__DIR__, 2) . '/config/routes/user.php';
        $route = RouteFactory::create('user-api-item', $routes['user-api-item']);

        $matched = $route->match($this->request('GET', '/api/users/0123abcd'));
        self::assertSame('0123abcd', $matched->parameters->get('id'));
        self::assertNull($route->match($this->request('GET', '/api/users/a/b')));
    }

    public function testCorsCoversUserApiRoutes(): void
    {
        $cors = new Cors(new Parameters(['oidc' => ['cors' => ['allowed_origins' => ['http://localhost:4200']]]]));
        self::assertTrue($cors->supports(new RouteMatched('user-api-item', 'user.api', 'item')));
        self::assertTrue($cors->supports(new RouteMatched('user-api-role', 'user.role-api', 'item')));
        self::assertTrue($cors->supports(new RouteMatched('user-api-permissions', 'user.role-api', 'permissions')));
        self::assertFalse($cors->supports(new RouteMatched('user-profile', 'user.profile', 'index')));

        $_SERVER = [
            'REQUEST_METHOD' => 'OPTIONS', 'REQUEST_URI' => '/api/users/u1',
            'HTTP_ORIGIN' => 'http://localhost:4200',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'DELETE',
            'HTTP_ACCESS_CONTROL_REQUEST_HEADERS' => 'authorization',
        ];
        $response = $cors->preflight(Request::fromGlobals(), new RouteMatched('user-api-item', 'user.api', 'item'));
        self::assertSame(204, $this->httpStatus($response));
        self::assertSame('GET, PUT, PATCH, DELETE', $response->headers->get('Access-Control-Allow-Methods'));
    }
}
