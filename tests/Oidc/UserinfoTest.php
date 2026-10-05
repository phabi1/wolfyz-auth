<?php

namespace App\Tests\Oidc;

use App\Oidc\Server\Entity\UserEntity;
use App\Oidc\Server\Repository\UserRepository;
use App\Oidc\Server\Server;
use App\Oidc\Userinfo;
use Firebase\JWT\JWT;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;
use League\OAuth2\Server\ResourceServer;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class UserinfoTest extends TestCase
{
    private static string $privateKey = '';
    private static string $publicKey;

    public static function setUpBeforeClass(): void
    {
        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        if ($key === false || !openssl_pkey_export($key, self::$privateKey)) {
            throw new \RuntimeException('Could not generate test signing key.');
        }
        $details = openssl_pkey_get_details($key);
        if ($details === false) {
            throw new \RuntimeException('Could not obtain test public key.');
        }
        self::$publicKey = $details['key'];
    }

    #[DataProvider('claimsProvider')]
    public function testReturnsOnlyGrantedClaims(array $scopes, UserEntity $user, array $expected): void
    {
        $userinfo = $this->userinfo($user);
        $data = $userinfo->build($this->request($this->token(['scopes' => $scopes])));

        self::assertSame($expected, $data);
        if (in_array('email', $scopes, true)) {
            self::assertIsBool($data['email_verified']);
        } else {
            self::assertArrayNotHasKey('email_verified', $data);
        }
        self::assertArrayNotHasKey('password_hash', $data);
    }

    public static function claimsProvider(): array
    {
        $user = new UserEntity('user-123', 'jane@example.com', 'Jane Doe', 'Jane', 'Doe');
        return [
            'openid only' => [['openid'], $user, ['sub' => 'user-123']],
            'profile' => [['openid', 'profile'], $user, [
                'sub' => 'user-123', 'name' => 'Jane Doe', 'given_name' => 'Jane', 'family_name' => 'Doe',
            ]],
            'email' => [['openid', 'email'], $user, [
                'sub' => 'user-123', 'email' => 'jane@example.com', 'email_verified' => false,
            ]],
            'verified email' => [['openid', 'email'], new UserEntity(
                'user-123', 'jane@example.com', 'Jane Doe', emailVerified: true
            ), ['sub' => 'user-123', 'email' => 'jane@example.com', 'email_verified' => true]],
            'verified without email scope' => [['openid'], new UserEntity(
                'user-123', 'jane@example.com', emailVerified: true
            ), ['sub' => 'user-123']],
            'all claims' => [['openid', 'profile', 'email'], $user, [
                'sub' => 'user-123', 'name' => 'Jane Doe', 'given_name' => 'Jane', 'family_name' => 'Doe',
                'email' => 'jane@example.com', 'email_verified' => false,
            ]],
            'optional names omitted' => [['openid', 'profile'], new UserEntity('user-123', '', 'Jane Doe'), [
                'sub' => 'user-123', 'name' => 'Jane Doe',
            ]],
            'unrelated scope' => [['openid', 'api'], $user, ['sub' => 'user-123']],
        ];
    }

    #[DataProvider('invalidHeaderProvider')]
    public function testRejectsMissingOrMalformedBearerHeader(array $headers): void
    {
        $userinfo = $this->userinfo(null, false, false);
        $this->assertError($userinfo, new ServerRequest('GET', '/oidc/userinfo', $headers), 'invalid_token', 401);
    }

    public static function invalidHeaderProvider(): array
    {
        return [
            'missing' => [[]],
            'empty bearer' => [['Authorization' => 'Bearer ']],
            'basic' => [['Authorization' => 'Basic abc']],
            'raw token' => [['Authorization' => 'abc.def.ghi']],
            'multiple headers' => [['Authorization' => ['Bearer abc', 'Bearer def']]],
        ];
    }

    #[DataProvider('invalidTokenProvider')]
    public function testRejectsInvalidTokens(array $claims, bool $revoked): void
    {
        $userinfo = $this->userinfo(null, $revoked, false);
        $this->assertError($userinfo, $this->request($this->token($claims)), 'invalid_token', 401);
    }

    public static function invalidTokenProvider(): array
    {
        return [
            'expired' => [['iat' => time() - 120, 'nbf' => time() - 120, 'exp' => time() - 60], false],
            'not yet valid' => [['nbf' => time() + 3600], false],
            'revoked' => [[], true],
            'no user subject' => [['sub' => ''], false],
        ];
    }

    public function testRejectsBadSignatureAndMalformedJwt(): void
    {
        $userinfo = $this->userinfo(null, false, false);
        $jwt = $this->token([]);
        $parts = explode('.', $jwt);
        $parts[2] = str_repeat('A', strlen($parts[2]));

        $this->assertError($userinfo, $this->request(implode('.', $parts)), 'invalid_token', 401);
        $this->assertError($userinfo, $this->request('not-a-jwt'), 'invalid_token', 401);
    }

    public function testRejectsMissingOpenidScope(): void
    {
        $userinfo = $this->userinfo(null, false, false);
        $this->assertError($userinfo, $this->request($this->token(['scopes' => ['profile', 'email']])), 'insufficient_scope', 403);
    }

    public function testRejectsDeletedUser(): void
    {
        $this->assertError($this->userinfo(null), $this->request($this->token([])), 'invalid_token', 401);
    }

    public function testRejectsIdToken(): void
    {
        $idToken = JWT::encode([
            'iss' => 'https://issuer.example',
            'aud' => 'club',
            'sub' => 'user-123',
            'iat' => time(),
            'exp' => time() + 3600,
        ], self::$privateKey, 'RS256');

        $this->assertError($this->userinfo(null, false, false), $this->request($idToken), 'invalid_token', 401);
    }

    public function testRejectsAccessTokenWithoutExpiry(): void
    {
        $jwt = JWT::encode([
            'jti' => 'access-123', 'aud' => ['club'], 'sub' => 'user-123', 'scopes' => ['openid'],
        ], self::$privateKey, 'RS256');

        $this->assertError($this->userinfo(null, false, false), $this->request($jwt), 'invalid_token', 401);
    }

    public function testAcceptsCaseInsensitiveBearerScheme(): void
    {
        $userinfo = $this->userinfo(new UserEntity('user-123'));
        $request = new ServerRequest('POST', '/oidc/userinfo', ['Authorization' => 'bearer ' . $this->token([])]);
        self::assertSame(['sub' => 'user-123'], $userinfo->build($request));
    }

    private function userinfo(?UserEntity $user, bool $revoked = false, bool $lookup = true): Userinfo
    {
        $tokens = $this->createMock(AccessTokenRepositoryInterface::class);
        $tokens->method('isAccessTokenRevoked')->willReturn($revoked);
        $resourceServer = new ResourceServer($tokens, self::$publicKey);
        $server = $this->createMock(Server::class);
        $server->method('resourceServer')->willReturn($resourceServer);
        $users = $this->createMock(UserRepository::class);
        $users->expects($lookup ? self::once() : self::never())
            ->method('getUserEntityByIdentifier')
            ->with('user-123')
            ->willReturn($user);

        return new Userinfo($server, $users);
    }

    private function token(array $claims): string
    {
        return JWT::encode(array_replace([
            'jti' => 'access-123',
            'aud' => ['club'],
            'sub' => 'user-123',
            'iat' => time() - 1,
            'nbf' => time() - 1,
            'exp' => time() + 3600,
            'scopes' => ['openid'],
        ], $claims), self::$privateKey, 'RS256');
    }

    private function request(string $token): ServerRequest
    {
        return new ServerRequest('GET', '/oidc/userinfo', ['Authorization' => 'Bearer ' . $token]);
    }

    private function assertError(Userinfo $userinfo, ServerRequest $request, string $error, int $status): void
    {
        try {
            $userinfo->build($request);
            self::fail('Expected UserInfo to reject the request.');
        } catch (OAuthServerException $e) {
            self::assertSame($error, $e->getErrorType());
            self::assertSame($status, $e->getHttpStatusCode());
        }
    }
}
