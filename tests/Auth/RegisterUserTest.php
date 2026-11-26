<?php

namespace App\Tests\Auth;

use App\Auth\Exception\EmailAlreadyExistsException;
use App\Auth\Exception\InvalidRegistrationException;
use App\Core\Db\Db;
use App\Core\Db\Handler\PdoHandler;
use App\Core\Di\Container;
use App\Core\Entity\EntityDefinition;
use App\Core\UseCase\UseCaseBus;
use App\Core\Session\Session;
use App\User\Password\PasswordEncoder;
use PHPUnit\Framework\TestCase;

class RegisterUserTest extends TestCase
{
    private Db $db;
    private UseCaseBus $bus;

    protected function setUp(): void
    {
        if (!defined('APP_DIR')) {
            define('APP_DIR', dirname(__DIR__, 2));
        }
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
        $handler->execute('CREATE TABLE auth_user_provider (
            user_id TEXT, auth_provider_id TEXT, value TEXT, PRIMARY KEY (user_id, auth_provider_id)
        )');
        $this->db = new Db($handler);
        $definitions = new EntityDefinition();
        foreach (array_merge(
            require APP_DIR . '/config/entities/user.php',
            require APP_DIR . '/config/entities/auth.php',
        ) as $name => $definition) {
            $definitions->register($name, $definition);
        }
        $session = $this->createMock(Session::class);
        $container = new Container(array_merge(
            require APP_DIR . '/config/services/core.php',
            require APP_DIR . '/config/services/user.php',
            require APP_DIR . '/config/services/auth.php',
            [
                'db' => ['factory' => fn () => $this->db],
                'entity.definition' => ['factory' => fn () => $definitions],
                'session' => ['factory' => fn () => $session],
            ],
        ));
        $this->bus = $container->get('use-case-bus');
    }

    private function register(array $overrides = []): \stdClass
    {
        return $this->bus->execute('auth.register-user', array_merge([
            'email' => ' jane@example.com ',
            'password' => 'secret-password',
            'firstname' => 'Jane',
            'lastname' => 'Doe',
        ], $overrides));
    }

    public function testRegistersUserWithHashedPasswordAndLocalProvider(): void
    {
        $user = $this->register();

        self::assertNotEmpty($user->id);
        self::assertSame('jane@example.com', $user->email);
        self::assertSame('Jane Doe', $user->name);
        self::assertSame('Jane', $user->firstname);
        self::assertSame('Doe', $user->lastname);
        self::assertTrue((new PasswordEncoder())->verify('secret-password', $user->password_hash));

        $provider = $this->db->row('SELECT * FROM auth_user_provider');
        self::assertSame($user->id, $provider->user_id);
        self::assertSame('local', $provider->auth_provider_id);
        self::assertSame('jane@example.com', $provider->value);
    }

    public function testRejectsDuplicateEmail(): void
    {
        $this->register();

        $this->expectException(EmailAlreadyExistsException::class);
        $this->register(['firstname' => 'Other']);
    }

    public function testRejectsInvalidInput(): void
    {
        foreach ([['email' => 'nope'], ['password' => 'short'], ['firstname' => ' ']] as $overrides) {
            try {
                $this->register($overrides);
                self::fail('Expected InvalidRegistrationException for ' . json_encode($overrides));
            } catch (InvalidRegistrationException) {
                self::assertSame(0, (int) $this->db->value('SELECT COUNT(*) FROM auth_user'));
            }
        }
    }
}
