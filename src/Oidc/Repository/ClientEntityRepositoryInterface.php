<?php
namespace App\Oidc\Repository;

interface ClientEntityRepositoryInterface
{
    public function findByClientId(string $clientId);
}