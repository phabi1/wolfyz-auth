<?php

namespace App\Tests\Oidc;

use App\Core\Entity\EntityManager;
use App\Core\Entity\EntityRepositoryInterface;
use App\Oidc\Jwks;
use App\Oidc\Server\Entity\UserEntity;
use App\Oidc\Server\IdTokenResponse;
use App\Oidc\Server\Repository\UserRepository;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use League\OAuth2\Server\CryptKey;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class EmailVerificationTest extends TestCase
{
    #[DataProvider('storedStatusProvider')]
    public function testRepositoryMapsStoredVerificationStatus(bool|int|string|null $stored, bool $expected): void
    {
        $row = (object) ['id' => 'user-123', 'email' => 'jane@example.com', 'name' => 'Jane Doe'];
        if ($stored !== null) {
            $row->email_verified = $stored;
        }
        $repository = $this->createMock(EntityRepositoryInterface::class);
        $repository->expects(self::once())->method('findOne')
            ->with(['id' => ['eq' => 'user-123']])->willReturn($row);
        $manager = $this->createMock(EntityManager::class);
        $manager->method('getRepository')->with('user')->willReturn($repository);

        $user = (new UserRepository($manager))->getUserEntityByIdentifier('user-123');

        self::assertInstanceOf(UserEntity::class, $user);
        self::assertSame($expected, $user->emailVerified);
    }

    public static function storedStatusProvider(): array
    {
        return [
            [true, true], [false, false], [1, true], [0, false],
            ['1', true], ['0', false], [null, false],
        ];
    }

    #[DataProvider('idTokenStatusProvider')]
    public function testIdTokenUsesActualVerificationStatus(bool $verified, bool $emailScope): void
    {
        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        $privateKey = '';
        if ($key === false || !openssl_pkey_export($key, $privateKey)) {
            throw new \RuntimeException('Could not generate signing key.');
        }
        $details = openssl_pkey_get_details($key);
        if ($details === false) {
            throw new \RuntimeException('Could not obtain public key.');
        }
        $path = tempnam(sys_get_temp_dir(), 'oidc-test-');
        if ($path === false) {
            throw new \RuntimeException('Could not create test key file.');
        }

        try {
            if (file_put_contents($path, $privateKey) === false) {
                throw new \RuntimeException('Could not write test key.');
            }
            $users = $this->createMock(UserRepository::class);
            $users->method('getUserEntityByIdentifier')->with('user-123')
                ->willReturn(new UserEntity('user-123', 'jane@example.com', emailVerified: $verified));
            $jwks = $this->createMock(Jwks::class);
            $jwks->method('keyId')->willReturn('test-key');
            $responseType = new IdTokenResponse($users, 'https://issuer.example', $path, $jwks);
            $responseType->setPrivateKey(new CryptKey($path, null, false));

            $scopes = [];
            foreach ($emailScope ? ['openid', 'email'] : ['openid'] as $name) {
                $scope = $this->createMock(ScopeEntityInterface::class);
                $scope->method('getIdentifier')->willReturn($name);
                $scopes[] = $scope;
            }
            $client = $this->createMock(ClientEntityInterface::class);
            $client->method('getIdentifier')->willReturn('club');
            $accessToken = $this->createMock(AccessTokenEntityInterface::class);
            $accessToken->method('getScopes')->willReturn($scopes);
            $accessToken->method('getUserIdentifier')->willReturn('user-123');
            $accessToken->method('getClient')->willReturn($client);
            $accessToken->method('getExpiryDateTime')->willReturn(new \DateTimeImmutable('+1 hour'));
            $accessToken->method('toString')->willReturn('test-access-token');
            $responseType->setAccessToken($accessToken);

            $response = $responseType->generateHttpResponse(new Response());
            $data = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
            $claims = JWT::decode($data['id_token'], new Key($details['key'], 'RS256'));

            if ($emailScope) {
                self::assertSame($verified, $claims->email_verified);
                self::assertSame('jane@example.com', $claims->email);
            } else {
                self::assertObjectNotHasProperty('email_verified', $claims);
                self::assertObjectNotHasProperty('email', $claims);
            }
        } finally {
            if (!unlink($path)) {
                throw new \RuntimeException('Could not remove test key.');
            }
        }
    }

    public static function idTokenStatusProvider(): array
    {
        return [[true, true], [false, true], [true, false], [false, false]];
    }
}
