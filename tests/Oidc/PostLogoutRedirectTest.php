<?php

namespace App\Tests\Oidc;

use App\Oidc\PostLogoutRedirect;
use App\Oidc\Server\Entity\ClientEntity;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\ClientRepositoryInterface;
use PHPUnit\Framework\TestCase;

class PostLogoutRedirectTest extends TestCase
{
    private PostLogoutRedirect $resolver;

    protected function setUp(): void
    {
        $clients = [
            'club' => new ClientEntity('club', 'club', ['http://localhost:4200', 'https://club.example.com/'], false),
            'other' => new ClientEntity('other', 'other', 'https://other.example.com/', false),
        ];

        $repository = new class ($clients) implements ClientRepositoryInterface {
            public function __construct(private array $clients)
            {
            }

            public function getClientEntity(string $clientIdentifier): ?ClientEntityInterface
            {
                return $this->clients[$clientIdentifier] ?? null;
            }

            public function validateClient(string $clientIdentifier, ?string $clientSecret, ?string $grantType): bool
            {
                return false;
            }
        };

        $this->resolver = new PostLogoutRedirect($repository);
    }

    /** Unsigned JWT: only the payload is read. */
    private function idToken(array $claims): string
    {
        $encode = static fn (array $data) => rtrim(strtr(base64_encode(json_encode($data)), '+/', '-_'), '=');

        return $encode(['typ' => 'JWT', 'alg' => 'RS256']) . '.' . $encode($claims) . '.c2ln';
    }

    public function testAcceptsARegisteredUriOfTheIdTokenAudience(): void
    {
        $hint = $this->idToken(['sub' => '1', 'aud' => 'club']);

        $this->assertSame('http://localhost:4200', $this->resolver->resolve('http://localhost:4200', null, $hint));
    }

    public function testAcceptsARegisteredUriOfTheClientIdParameter(): void
    {
        $this->assertSame('https://other.example.com/', $this->resolver->resolve('https://other.example.com/', 'other'));
    }

    public function testRejectsAnUnregisteredUri(): void
    {
        $hint = $this->idToken(['sub' => '1', 'aud' => 'club']);

        $this->assertNull($this->resolver->resolve('https://evil.example.com', null, $hint));
        $this->assertNull($this->resolver->resolve('http://localhost:4200.evil.com', 'club'));
    }

    public function testRejectsAUriOfAnotherClient(): void
    {
        $hint = $this->idToken(['sub' => '1', 'aud' => 'club']);

        $this->assertNull($this->resolver->resolve('https://other.example.com/', null, $hint));
    }

    public function testRejectsAClientIdThatDoesNotMatchTheIdToken(): void
    {
        $hint = $this->idToken(['sub' => '1', 'aud' => 'club']);

        $this->assertNull($this->resolver->resolve('https://other.example.com/', 'other', $hint));
    }

    public function testRejectsWithoutAnyClient(): void
    {
        $this->assertNull($this->resolver->resolve('http://localhost:4200'));
        $this->assertNull($this->resolver->resolve('http://localhost:4200', null, 'not-a-jwt'));
        $this->assertNull($this->resolver->resolve('http://localhost:4200', 'unknown'));
    }

    public function testReturnsNullWithoutUri(): void
    {
        $this->assertNull($this->resolver->resolve(null, 'club'));
        $this->assertNull($this->resolver->resolve('', 'club'));
    }

    public function testAppendsTheState(): void
    {
        $this->assertSame('http://localhost:4200?state=a%20b', $this->resolver->resolve('http://localhost:4200', 'club', null, 'a b'));
    }
}
