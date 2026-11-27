<?php

namespace App\Tests\User;

use App\Core\Db\Db;
use App\Core\Db\Handler\PdoHandler;
use App\Core\Di\Container;
use App\Core\Entity\EntityDefinition;
use App\Core\Session\Session;
use PHPUnit\Framework\TestCase;

/**
 * In-memory SQLite database with the user, role and OAuth tables, and the
 * application container wired to it.
 */
abstract class UserTestCase extends TestCase
{
    protected Db $db;
    protected Container $container;

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
        foreach ([
            'CREATE TABLE auth_user (
                id TEXT PRIMARY KEY, email TEXT UNIQUE, name TEXT, password_hash TEXT,
                email_verified INTEGER DEFAULT 0, firstname TEXT, lastname TEXT,
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
            )',
            'CREATE TABLE auth_user_provider (user_id TEXT, auth_provider_id TEXT, value TEXT, PRIMARY KEY (user_id, auth_provider_id))',
            'CREATE TABLE auth_role (id TEXT PRIMARY KEY, label TEXT NOT NULL, description TEXT NULL,
                is_system INTEGER NOT NULL DEFAULT 0, created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP)',
            'CREATE TABLE auth_role_permission (role_id TEXT, permission TEXT, PRIMARY KEY (role_id, permission))',
            'CREATE TABLE auth_user_role (user_id TEXT, role_id TEXT, PRIMARY KEY (user_id, role_id))',
            'CREATE TABLE auth_oauth_client_role (client_id TEXT, role_id TEXT, PRIMARY KEY (client_id, role_id))',
            "INSERT INTO auth_role (id, label, is_system) VALUES ('admin', 'Administrator', 1)",
            'CREATE TABLE auth_oauth_access_token (id TEXT PRIMARY KEY, user_id TEXT, revoked INTEGER DEFAULT 0)',
            'CREATE TABLE auth_oauth_refresh_token (id TEXT PRIMARY KEY, access_token_id TEXT, revoked INTEGER DEFAULT 0)',
            'CREATE TABLE auth_oauth_auth_code (id TEXT PRIMARY KEY, user_id TEXT, revoked INTEGER DEFAULT 0)',
        ] as $sql) {
            $handler->execute($sql);
        }
        $this->db = new Db($handler);

        $definitions = new EntityDefinition();
        foreach (array_merge(
            require APP_DIR . '/config/entities/user.php',
            require APP_DIR . '/config/entities/auth.php',
        ) as $name => $definition) {
            $definitions->register($name, $definition);
        }
        $session = $this->createMock(Session::class);
        $this->container = new Container(array_merge(
            require APP_DIR . '/config/services/core.php',
            require APP_DIR . '/config/services/user.php',
            require APP_DIR . '/config/services/auth.php',
            [
                'db' => ['factory' => fn () => $this->db],
                'entity.definition' => ['factory' => fn () => $definitions],
                'session' => ['factory' => fn () => $session],
            ],
        ));
    }
}
