<?php

namespace App\Tests\Oidc;

use App\Oidc\Server\Entity\AccessTokenEntity;
use App\Oidc\Server\Entity\ClientEntity;
use App\Oidc\Server\Entity\ScopeEntity;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use League\OAuth2\Server\CryptKey;
use PHPUnit\Framework\TestCase;

class AccessTokenEntityTest extends TestCase
{
    private static string $privateKeyPath;
    private static string $publicKey;
    private array $header = [];

    public static function setUpBeforeClass(): void
    {
        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($key, $privateKey);
        self::$publicKey = openssl_pkey_get_details($key)['key'];
        self::$privateKeyPath = tempnam(sys_get_temp_dir(), 'key');
        file_put_contents(self::$privateKeyPath, $privateKey);
        chmod(self::$privateKeyPath, 0600);
    }

    public static function tearDownAfterClass(): void
    {
        @unlink(self::$privateKeyPath);
    }

    private function token(?array $permissions, ?string $issuer): array
    {
        $token = new AccessTokenEntity();
        $token->setIdentifier('token-1');
        $token->setClient(new ClientEntity('club', 'club', 'http://localhost:4200', false));
        $token->setUserIdentifier('user-1');
        $token->setExpiryDateTime(new \DateTimeImmutable('+1 hour'));
        foreach (['openid', 'api'] as $scope) {
            $token->addScope(new ScopeEntity($scope));
        }
        if ($permissions !== null) {
            $token->setPermissions($permissions);
        }
        $token->setIssuer($issuer);
        $token->setKeyId($issuer === null ? null : 'key-1');
        $token->setPrivateKey(new CryptKey(self::$privateKeyPath, null, false));

        $jwt = $token->toString();
        $this->header = json_decode(base64_decode(strtr(explode('.', $jwt)[0], '-_', '+/')), true);

        return (array) JWT::decode($jwt, new Key(self::$publicKey, 'RS256'));
    }

    public function testCarriesTheIssuerAndThePermissions(): void
    {
        $claims = $this->token(['membership.request.view'], 'https://auth.example.com');

        self::assertSame('https://auth.example.com', $claims['iss']);
        self::assertSame(['membership.request.view'], $claims['permissions']);
        self::assertSame(['openid', 'api'], $claims['scopes']);
        self::assertSame('user-1', $claims['sub']);
        self::assertSame('club', $claims['aud']);
        self::assertSame('token-1', $claims['jti']);
        self::assertSame('key-1', $this->header['kid']);
    }

    public function testOmitsTheClaimsThatAreNotSet(): void
    {
        $claims = $this->token(null, null);

        self::assertArrayNotHasKey('iss', $claims);
        self::assertArrayNotHasKey('permissions', $claims);
        self::assertArrayNotHasKey('kid', $this->header);
    }
}
