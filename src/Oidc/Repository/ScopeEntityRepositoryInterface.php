<?php
namespace App\Oidc\Repository;

use App\Core\Entity\EntityRepositoryInterface;

interface ScopeEntityRepositoryInterface extends EntityRepositoryInterface
{
    public function findByName(string $name);
}