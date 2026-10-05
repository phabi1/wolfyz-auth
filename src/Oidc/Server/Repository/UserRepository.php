<?php

namespace App\Oidc\Server\Repository;

use App\Core\Entity\EntityManager;
use App\Core\Entity\EntityRepository;
use App\Core\Entity\EntityRepositoryInterface;
use App\Oidc\Server\Entity\UserEntity;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\UserEntityInterface;
use League\OAuth2\Server\Repositories\UserRepositoryInterface;

class UserRepository implements UserRepositoryInterface
{
    private readonly EntityRepositoryInterface $userEntityRepository;

    public function __construct(EntityManager $entityManager)
    {
        $this->userEntityRepository = $entityManager->getRepository('user');
    }

    public function getUserEntityByUserCredentials(
        string $username,
        string $password,
        string $grantType,
        ClientEntityInterface $clientEntity
    ): ?UserEntityInterface {
        $row = $this->findByEmail($username);

        if (!$row || !password_verify($password, $row->password_hash)) {
            return null;
        }

        return $this->toEntity($row);
    }

    public function getUserEntityByIdentifier(string $identifier): ?UserEntityInterface
    {
        $row = $this->userEntityRepository->findOne(['id' => ['eq' => $identifier]]);

        return $row ? $this->toEntity($row) : null;
    }

    public function findByEmail(string $email): ?\stdClass
    {
        return $this->userEntityRepository->findOne(['email' => ['eq' => $email]]); 
    }

    public function create(string $email, string $password, string $name): UserEntity
    {
        $id = bin2hex(random_bytes(16));

        $this->userEntityRepository->insert([
            'id' => $id,
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'name' => $name,
        ]);

        return new UserEntity($id, $email, $name);
    }

    private function toEntity(\stdClass $row): UserEntity
    {
        return new UserEntity(
            $row->id,
            $row->email,
            $row->name,
            $row->given_name ?? null,
            $row->family_name ?? null,
            (bool) ($row->email_verified ?? false),
        );
    }
}
