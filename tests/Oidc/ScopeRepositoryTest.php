<?php

namespace App\Tests\Oidc;

use App\Core\Entity\EntityManager;
use App\Core\Entity\EntityRepositoryInterface;
use App\Core\Watchdog\WatchdogService;
use App\Oidc\Repository\ClientEntityRepositoryInterface;
use App\Oidc\Repository\ScopeEntityRepositoryInterface;
use App\Oidc\Server\Entity\ScopeEntity;
use App\Oidc\Server\Repository\ScopeRepository;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use PHPUnit\Framework\TestCase;

class ScopeRepositoryTest extends TestCase
{
    public function testKeepsClientScopesAndWarnsAboutDroppedOnes(): void
    {
        $watchdog = $this->createMock(WatchdogService::class);
        $watchdog->expects(self::once())->method('warning')->with(
            self::isType('string'),
            self::callback(fn (array $data) => $data['client_id'] === 'club'
                && $data['grant_type'] === 'authorization_code'
                && $data['dropped_scopes'] === ['profile', 'roles']
                && $data['client_scopes'] === 'openid email'),
        );

        $scopes = $this->repository('openid email', $watchdog)->finalizeScopes(
            $this->scopes('openid', 'profile', 'email', 'roles'),
            'authorization_code',
            $this->client('club'),
        );

        self::assertSame(['openid', 'email'], array_map(fn ($scope) => $scope->getIdentifier(), $scopes));
    }

    public function testDoesNotWarnWhenEveryScopeIsAllowed(): void
    {
        $watchdog = $this->createMock(WatchdogService::class);
        $watchdog->expects(self::never())->method('warning');

        $scopes = $this->repository('openid profile', $watchdog)->finalizeScopes(
            $this->scopes('openid', 'profile'),
            'refresh_token',
            $this->client('club'),
        );

        self::assertCount(2, $scopes);
    }

    private function repository(string $clientScopes, WatchdogService $watchdog): ScopeRepository
    {
        $clientRepository = $this->createStubForIntersectionOfInterfaces([
            EntityRepositoryInterface::class,
            ClientEntityRepositoryInterface::class,
        ]);
        $clientRepository->method('findByClientId')->willReturn((object) ['scopes' => $clientScopes]);

        $entityManager = $this->createStub(EntityManager::class);
        $entityManager->method('getRepository')->willReturnMap([
            ['oidc-scope', $this->createStub(ScopeEntityRepositoryInterface::class)],
            ['oidc-client', $clientRepository],
        ]);

        $repository = new ScopeRepository($entityManager);
        $repository->setWatchdogService($watchdog);

        return $repository;
    }

    private function scopes(string ...$names): array
    {
        return array_map(fn (string $name) => new ScopeEntity($name), $names);
    }

    private function client(string $id): ClientEntityInterface
    {
        $client = $this->createStub(ClientEntityInterface::class);
        $client->method('getIdentifier')->willReturn($id);

        return $client;
    }
}
