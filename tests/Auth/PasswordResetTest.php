<?php

namespace App\Tests\Auth;

use App\Auth\Controller\PasswordController;
use App\Auth\Password\PasswordResetMailException;
use App\Auth\Password\PasswordResetService;
use App\Core\Config\Parameters;
use App\Core\Db\Db;
use App\Core\Db\Handler\PdoHandler;
use App\Core\Di\Container;
use App\Core\Entity\EntityDefinition;
use App\Core\Entity\EntityManager;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Mail\Mailer;
use App\Core\Session\Session;
use App\User\Password\PasswordEncoder;
use App\User\Repository\UserRepository;
use App\User\Token\TokenService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionProperty;

class PasswordResetTest extends TestCase
{
    private Db $db;
    private EntityManager $entities;
    private TokenService $tokens;
    private PasswordResetService $service;
    private Container $container;
    private array $sessionData;
    private array $globals;
    private Mailer&MockObject $mailer;
    private bool $mailConfigured;

    protected function setUp(): void
    {
        if (!defined('APP_DIR')) {
            define('APP_DIR', dirname(__DIR__, 2));
        }
        $this->globals = [$_SERVER, $_GET, $_POST];
        $_SERVER = ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/auth/password/forgot'];
        $_GET = $_POST = [];
        $handler = new class('sqlite::memory:') extends PdoHandler {
            public function execute($sql)
            {
                return parent::execute($sql === 'START TRANSACTION' ? 'BEGIN TRANSACTION' : $sql);
            }
        };
        $handler->execute('CREATE TABLE auth_user (
            id TEXT PRIMARY KEY, email TEXT UNIQUE, name TEXT, password_hash TEXT,
            email_verified INTEGER DEFAULT 0, firstname TEXT, lastname TEXT
        )');
        $handler->execute('CREATE TABLE auth_user_token (
            id TEXT PRIMARY KEY, user_id TEXT, token_type TEXT, token TEXT UNIQUE, expires_at DATETIME
        )');
        $this->db = new Db($handler);
        $definitions = new EntityDefinition();
        foreach (require APP_DIR . '/config/entities/user.php' as $name => $definition) {
            $definitions->register($name, $definition);
        }
        $this->sessionData = [];
        $this->mailConfigured = false;
        $this->mailer = $this->createMock(Mailer::class);
        $this->mailer->method('isConfigured')->willReturnCallback(fn () => $this->mailConfigured);
        $session = $this->createMock(Session::class);
        $session->method('get')->willReturnCallback(fn ($key, $default = null) => $this->sessionData[$key] ?? $default);
        $session->method('has')->willReturnCallback(fn ($key) => isset($this->sessionData[$key]));
        $session->method('set')->willReturnCallback(function ($key, $value): void {
            $this->sessionData[$key] = $value;
        });
        $this->container = new Container(array_merge(
            require APP_DIR . '/config/services/core.php',
            require APP_DIR . '/config/services/user.php',
            require APP_DIR . '/config/services/auth.php',
            [
                'db' => ['factory' => fn () => $this->db],
                'entity.definition' => ['factory' => fn () => $definitions],
                'session' => ['factory' => fn () => $session],
                'mailer' => ['factory' => fn () => $this->mailer],
                'parameters' => ['factory' => fn () => new Parameters(['oauth2' => ['issuer' => 'https://auth.example.com']])],
            ],
        ));
        $this->entities = $this->container->get('entity.manager');
        $this->tokens = $this->container->get('user.token');
        $this->service = $this->container->get('auth.password-reset');
        $this->entities->getRepository('user')->insert([
            'id' => 'user-123', 'email' => 'jane@example.com', 'name' => 'Jane',
            'password_hash' => 'original-password',
        ]);
    }

    protected function tearDown(): void
    {
        [$_SERVER, $_GET, $_POST] = $this->globals;
    }

    public function testGeneratesHashedExpiringTokensWithCorrectEntityFields(): void
    {
        $start = time();
        $token = $this->token();
        $row = $this->db->row('SELECT * FROM auth_user_token');
        self::assertMatchesRegularExpression('/\A[0-9a-f]{64}\z/', $token);
        self::assertSame(hash('sha256', $token), $row->token);
        self::assertSame(PasswordResetService::SUBJECT, $row->token_type);
        self::assertGreaterThanOrEqual($start + 3600, strtotime($row->expires_at));
        self::assertLessThanOrEqual(time() + 3600, strtotime($row->expires_at));
        self::assertTrue($this->tokens->validateToken($token, PasswordResetService::SUBJECT));
        self::assertFalse($this->tokens->validateToken($token, 'another-subject'));
        self::assertTrue($this->tokens->revokeToken($token));
        self::assertFalse($this->tokens->validateToken($token, PasswordResetService::SUBJECT));
    }

    public function testSupportsNonExpiringGenericTokens(): void
    {
        $token = $this->tokens->generateToken('user-123', 'other');
        self::assertNull($this->db->row('SELECT * FROM auth_user_token')->expires_at);
        self::assertTrue($this->tokens->validateToken($token, 'other'));
    }

    public function testSendsLinkUsingConfiguredIssuer(): void
    {
        $this->mailer->expects(self::once())->method('sendMail')
            ->with('jane@example.com', 'auth/password/reset', self::callback(function (array $variables): bool {
                $url = $variables['url'];
                self::assertStringStartsWith('https://auth.example.com/auth/password/reset?token=', $url);
                parse_str(parse_url($url, PHP_URL_QUERY), $query);
                self::assertTrue($this->service->isValidToken($query['token']));
                return true;
            }))->willReturn(true);
        $this->service->requestReset('jane@example.com', 'https://auth.example.com/');
    }

    public function testUnknownEmailDoesNotCreateTokenOrSendEmail(): void
    {
        $this->mailer->expects(self::never())->method('sendMail');
        $this->service->requestReset('unknown@example.com', 'https://auth.example.com');
        self::assertSame(0, $this->tokenCount());
    }

    public function testMailerExceptionsRevokeNewToken(): void
    {
        $this->mailer->method('sendMail')->willThrowException(new \PHPMailer\PHPMailer\Exception('SMTP unavailable.'));
        try {
            $this->service->requestReset('jane@example.com', 'https://auth.example.com');
            self::fail('SMTP failure should propagate as a password reset mail error.');
        } catch (PasswordResetMailException $exception) {
            self::assertInstanceOf(\PHPMailer\PHPMailer\Exception::class, $exception->getPrevious());
            self::assertSame(0, $this->tokenCount());
        }
    }

    public function testMailTemplatesRenderSubjectHtmlAndText(): void
    {
        $view = $this->container->get('view');
        $url = 'https://auth.example.com/auth/password/reset?token=' . str_repeat('a', 64);
        self::assertSame('Reset your Wolf Auth password', trim($view->render('mails/auth/password/reset.subject', ['url' => $url])));
        self::assertStringContainsString('href="' . $url . '"', $view->render('mails/auth/password/reset.html', ['url' => $url]));
        self::assertStringContainsString($url, $view->render('mails/auth/password/reset.text', ['url' => $url]));
    }

    public function testMailerRequiresSenderAndSmtpConfiguration(): void
    {
        $view = $this->container->get('view');
        self::assertFalse((new Mailer($view, new Parameters()))->isConfigured());
        self::assertTrue((new Mailer($view, new Parameters(['mail' => [
            'from' => ['email' => 'auth@example.com'],
            'smtp' => ['host' => 'localhost', 'port' => 1025],
        ]])))->isConfigured());
    }

    public function testDeliveryFailureRevokesOnlyNewToken(): void
    {
        $existing = $this->token();
        $this->mailer->method('sendMail')->willReturn(false);
        try {
            $this->service->requestReset('jane@example.com', 'https://auth.example.com');
            self::fail('Delivery failure should propagate.');
        } catch (PasswordResetMailException) {
            self::assertSame(1, $this->tokenCount());
            self::assertTrue($this->service->isValidToken($existing));
        }
    }

    public function testResetHashesPasswordInvalidatesAllResetTokensAndRejectsReplay(): void
    {
        $token = $this->token();
        $other = $this->token();
        $unrelated = $this->tokens->generateToken('user-123', 'another-subject');
        self::assertTrue($this->service->resetPassword($token, 'new-password'));
        $user = $this->entities->getRepository('user')->findById('user-123');
        $encoder = new PasswordEncoder();
        self::assertTrue($encoder->verify('new-password', $user->password_hash));
        self::assertFalse($encoder->verify('original-password', $user->password_hash));
        self::assertFalse($this->service->isValidToken($other));
        self::assertFalse($this->service->resetPassword($token, 'another-password'));
        self::assertTrue($this->tokens->validateToken($unrelated, 'another-subject'));
    }

    #[DataProvider('invalidTokenProvider')]
    public function testRejectsInvalidOrExpiredTokens(string $kind): void
    {
        $token = $this->token();
        if ($kind === 'expired' || $kind === 'at-expiry') {
            $this->db->update('auth_user_token', [
                'expires_at' => date('Y-m-d H:i:s', time() - ($kind === 'expired' ? 60 : 0)),
            ], '1 = 1');
        } elseif ($kind === 'wrong-subject') {
            $token = $this->tokens->generateToken('user-123', 'other');
        } elseif ($kind === 'missing') {
            $token = str_repeat('0', 64);
        } else {
            $token = 'malformed';
        }
        self::assertFalse($this->service->isValidToken($token));
        self::assertFalse($this->service->resetPassword($token, 'new-password'));
        self::assertTrue((new PasswordEncoder())->verify(
            'original-password', $this->entities->getRepository('user')->findById('user-123')->password_hash,
        ));
    }

    public static function invalidTokenProvider(): array
    {
        return array_map(fn ($kind) => [$kind], ['expired', 'at-expiry', 'wrong-subject', 'missing', 'malformed']);
    }

    public function testPasswordFailureRollsBackTokenConsumption(): void
    {
        $token = $this->token();
        $users = $this->createMock(UserRepository::class);
        $users->expects(self::once())->method('beginTransaction')
            ->willReturnCallback(fn () => $this->db->beginTransaction());
        $users->expects(self::once())->method('rollback')
            ->willReturnCallback(fn () => $this->db->rollback());
        $users->expects(self::never())->method('commit');
        $users->method('findById')->willReturn((object) ['id' => 'user-123']);
        $users->method('update')->willThrowException(new \RuntimeException('Database unavailable.'));
        $manager = $this->createMock(EntityManager::class);
        $manager->method('getRepository')->with('user')->willReturn($users);
        $manager->method('getDb')->willReturn($this->db);
        $service = new PasswordResetService($manager, $this->tokens, $this->mailer);
        try {
            $service->resetPassword($token, 'new-password');
            self::fail('Database failure should propagate.');
        } catch (\RuntimeException $exception) {
            self::assertSame('Database unavailable.', $exception->getMessage());
            self::assertTrue($this->service->isValidToken($token));
        }
    }

    #[DataProvider('passwordBoundaryProvider')]
    public function testAcceptsExactPasswordLengthBoundaries(int $length): void
    {
        $password = str_repeat('x', $length);
        self::assertTrue($this->service->resetPassword($this->token(), $password));
        self::assertTrue((new PasswordEncoder())->verify(
            $password, $this->entities->getRepository('user')->findById('user-123')->password_hash,
        ));
    }

    public static function passwordBoundaryProvider(): array
    {
        return [[8], [72]];
    }

    public function testMissingUserCannotResetPassword(): void
    {
        $token = $this->token();
        $this->entities->getRepository('user')->delete('user-123');
        self::assertFalse($this->service->isValidToken($token));
        self::assertFalse($this->service->resetPassword($token, 'new-password'));
    }

    public function testRejectsInvalidIssuerBeforeCreatingToken(): void
    {
        $this->mailer->expects(self::never())->method('sendMail');
        $this->expectException(\InvalidArgumentException::class);
        $this->service->requestReset('jane@example.com', 'javascript:alert(1)');
    }

    public function testForgotReportsDeliveryFailureWithoutClaimingSuccess(): void
    {
        $this->mailer->method('sendMail')->willReturn(false);
        $controller = $this->controller($this->mailer);
        $controller->forgotAction(Request::fromGlobals());
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['email' => 'jane@example.com', 'csrf' => $this->sessionData['password_reset_csrf']];
        $response = $controller->forgotAction(Request::fromGlobals());
        self::assertSame(503, $this->responseStatus($response));
        self::assertStringNotContainsString('If an account exists with this email', $this->body($response));
        self::assertSame(0, $this->tokenCount());
    }

    public function testConditionalConsumptionRejectsLostRace(): void
    {
        $token = $this->token();
        $db = $this->createMock(Db::class);
        $db->method('expr')->willReturn($this->db->expr());
        $db->method('escape')->willReturnCallback(fn ($value) => $this->db->escape($value));
        $db->expects(self::once())->method('delete')->willReturn(0);
        $manager = $this->createMock(EntityManager::class);
        $manager->method('getRepository')->willReturn($this->entities->getRepository('user-token'));
        $manager->method('getDb')->willReturn($db);
        self::assertNull((new TokenService($manager))->consumeToken($token, PasswordResetService::SUBJECT));
    }

    public function testForgotFormRendersAndUnavailableMailerReturns503(): void
    {
        $controller = $this->controller();
        $form = $controller->forgotAction(Request::fromGlobals());
        self::assertSame(200, $this->responseStatus($form));
        self::assertStringContainsString('name="csrf"', $this->body($form));
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['email' => 'jane@example.com', 'csrf' => $this->sessionData['password_reset_csrf']];
        $response = $controller->forgotAction(Request::fromGlobals());
        self::assertSame(503, $this->responseStatus($response));
        self::assertStringContainsString('currently unavailable', $this->body($response));
        self::assertSame(0, $this->tokenCount());
    }

    #[DataProvider('emailProvider')]
    public function testForgotReturnsIdenticalSuccessForKnownAndUnknownEmails(string $email): void
    {
        $this->mailer->method('sendMail')->willReturn(true);
        $controller = $this->controller($this->mailer);
        $controller->forgotAction(Request::fromGlobals());
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['email' => $email, 'csrf' => $this->sessionData['password_reset_csrf']];
        $response = $controller->forgotAction(Request::fromGlobals());
        self::assertSame(200, $this->responseStatus($response));
        self::assertStringContainsString('If an account exists with this email', $this->body($response));
        self::assertSame(429, $this->responseStatus($controller->forgotAction(Request::fromGlobals())));
    }

    public static function emailProvider(): array
    {
        return [['jane@example.com'], ['unknown@example.com']];
    }

    public function testForgotRejectsBadCsrfAndInvalidEmailWithoutCreatingToken(): void
    {
        $controller = $this->controller();
        $controller->forgotAction(Request::fromGlobals());
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['email' => 'jane@example.com'];
        self::assertSame(403, $this->responseStatus($controller->forgotAction(Request::fromGlobals())));
        $_POST = ['email' => 'invalid', 'csrf' => $this->sessionData['password_reset_csrf']];
        self::assertSame(400, $this->responseStatus($controller->forgotAction(Request::fromGlobals())));
        self::assertSame(0, $this->tokenCount());
    }

    public function testResetFormAndSuccessfulSubmission(): void
    {
        $controller = $this->controller();
        $_GET = ['token' => $this->token()];
        $response = $controller->resetAction(Request::fromGlobals());
        self::assertSame(200, $this->responseStatus($response));
        self::assertStringContainsString('name="password_confirmation"', $this->body($response));
        self::assertSame('no-store', $response->headers->get('Cache-Control'));
        self::assertSame('no-referrer', $response->headers->get('Referrer-Policy'));
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'token' => $_GET['token'], 'password' => 'new-password', 'password_confirmation' => 'new-password',
            'csrf' => $this->sessionData['password_reset_csrf'],
        ];
        $response = $controller->resetAction(Request::fromGlobals());
        self::assertSame(200, $this->responseStatus($response));
        self::assertStringContainsString('Your password has been reset', $this->body($response));
        self::assertStringNotContainsString('name="token"', $this->body($response));
        self::assertArrayNotHasKey('user_id', $this->sessionData);
    }

    #[DataProvider('badPasswordProvider')]
    public function testResetRejectsBadPasswordOrCsrf(string $password, string $confirmation, string $csrf, int $status): void
    {
        $controller = $this->controller();
        $token = $this->token();
        $controller->forgotAction(Request::fromGlobals());
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'token' => $token, 'password' => $password, 'password_confirmation' => $confirmation,
            'csrf' => $csrf === 'valid' ? $this->sessionData['password_reset_csrf'] : '',
        ];
        self::assertSame($status, $this->responseStatus($controller->resetAction(Request::fromGlobals())));
        self::assertTrue($this->service->isValidToken($token));
    }

    public static function badPasswordProvider(): array
    {
        return [
            ['short', 'short', 'valid', 400],
            [str_repeat('x', 73), str_repeat('x', 73), 'valid', 400],
            ['new-password', 'different-password', 'valid', 400],
            ['new-password', 'new-password', 'invalid', 403],
        ];
    }

    public function testInvalidResetLinkReturns400AndNoPasswordInputs(): void
    {
        $response = $this->controller()->resetAction(Request::fromGlobals());
        self::assertSame(400, $this->responseStatus($response));
        self::assertStringContainsString('invalid or has expired', $this->body($response));
        self::assertStringNotContainsString('name="password"', $this->body($response));
    }

    public function testUnsupportedMethodsAreRejected(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'DELETE';
        $controller = $this->controller();
        foreach (['forgotAction', 'resetAction'] as $action) {
            $response = $controller->$action(Request::fromGlobals());
            self::assertSame(405, $this->responseStatus($response));
            self::assertSame('GET, POST', $response->headers->get('Allow'));
        }
    }

    public function testRoutesAreWiredToController(): void
    {
        $routes = require APP_DIR . '/config/routes/auth.php';
        foreach (['forgot', 'reset'] as $action) {
            self::assertSame('/auth/password/' . $action, $routes['auth-password-' . $action]['path']);
            self::assertSame('auth.password', $routes['auth-password-' . $action]['controller']);
            self::assertSame($action, $routes['auth-password-' . $action]['action']);
        }
        self::assertInstanceOf(PasswordController::class, $this->container->get('controller')->get('auth.password'));
    }

    private function controller(?Mailer $mailer = null): PasswordController
    {
        $container = $this->container;
        if ($mailer !== null) {
            $this->mailConfigured = true;
            $container = new Container([
                'mailer' => ['factory' => fn () => $mailer],
                'auth.password-reset' => ['factory' => fn () => $this->service],
                'view' => ['factory' => fn () => $this->container->get('view')],
                'session' => ['factory' => fn () => $this->container->get('session')],
                'parameters' => ['factory' => fn () => $this->container->get('parameters')],
            ]);
        }
        $controller = new PasswordController();
        $controller->setContainer($container);
        return $controller;
    }

    private function token(): string
    {
        return $this->tokens->generateToken('user-123', PasswordResetService::SUBJECT, PasswordResetService::TTL);
    }

    private function tokenCount(): int
    {
        return (int) $this->db->value('SELECT COUNT(*) FROM auth_user_token');
    }

    private function responseStatus(Response $response): int
    {
        return (new ReflectionProperty(Response::class, 'status'))->getValue($response);
    }

    private function body(Response $response): string
    {
        return (new ReflectionProperty(Response::class, 'body'))->getValue($response);
    }
}
