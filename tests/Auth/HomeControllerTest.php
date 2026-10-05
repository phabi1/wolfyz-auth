<?php

namespace App\Tests\Auth;

use App\Auth\Controller\HomeController;
use App\Core\Di\Container;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Session\Session;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

class HomeControllerTest extends TestCase
{
    private array $globals;

    protected function setUp(): void
    {
        if (!defined('APP_DIR')) {
            define('APP_DIR', dirname(__DIR__, 2));
        }
        if (!defined('APP_ENV')) {
            define('APP_ENV', 'development');
        }
        $this->globals = [$_SERVER, $_GET, $_POST];
        $_SERVER = ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/'];
        $_GET = $_POST = [];
    }

    protected function tearDown(): void
    {
        [$_SERVER, $_GET, $_POST] = $this->globals;
    }

    private function container(bool $isLoggedIn): Container
    {
        $session = $this->createMock(Session::class);
        $session->expects(self::once())->method('get')->with('user_id')->willReturn($isLoggedIn ? 'user-123' : null);
        $session->expects(self::never())->method('remove');
        $definitions = require APP_DIR . '/config/services.php';
        $definitions['session'] = ['factory' => fn () => $session];
        return new Container($definitions);
    }

    private function body(Response $response): string
    {
        return (new ReflectionProperty(Response::class, 'body'))->getValue($response);
    }

    public function testGuestHomepageIsRenderedThroughRootRoute(): void
    {
        $container = $this->container(false);
        $request = Request::fromGlobals();
        $route = $container->get('route.matcher')->match($request);
        self::assertNotNull($route);
        self::assertSame('auth-home', $route->routeName);
        self::assertSame('auth.home', $route->controller);
        self::assertSame('index', $route->action);
        $controller = $container->get('controller')->get($route->controller);
        self::assertInstanceOf(HomeController::class, $controller);
        $response = $controller->dispatch($route->action, $request);
        self::assertSame(200, (new ReflectionProperty(Response::class, 'status'))->getValue($response));
        self::assertSame('no-store', $response->headers->get('Cache-Control'));
        $body = $this->body($response);
        self::assertStringContainsString('Bienvenue sur Wolf Auth', $body);
        self::assertStringContainsString('<html lang="fr">', $body);
        self::assertStringContainsString('href="/signin"', $body);
        self::assertStringContainsString('href="/signup"', $body);
        self::assertStringContainsString('href="/auth/password/forgot"', $body);
        self::assertStringNotContainsString('href="/signout"', $body);
    }

    public function testSignedInHomepageShowsAccountLinks(): void
    {
        $response = $this->container(true)->get('controller')->get('auth.home')->indexAction(Request::fromGlobals());
        $body = $this->body($response);
        self::assertStringContainsString('href="/profile"', $body);
        self::assertStringContainsString('Voir mon profil', $body);
        self::assertStringContainsString('href="/signout"', $body);
        self::assertStringNotContainsString('href="/signin"', $body);
        self::assertStringNotContainsString('href="/signup"', $body);
    }

    public function testHeadHasNoResponseBody(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'HEAD';
        $response = $this->container(false)->get('controller')->get('auth.home')->indexAction(Request::fromGlobals());
        self::assertSame('', $this->body($response));
        self::assertSame('no-store', $response->headers->get('Cache-Control'));
    }

    public function testUnsupportedMethodDoesNotAccessSession(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $response = (new HomeController())->indexAction(Request::fromGlobals());
        self::assertSame(405, (new ReflectionProperty(Response::class, 'status'))->getValue($response));
        self::assertSame('GET, HEAD', $response->headers->get('Allow'));
    }
}
