<?php

namespace App\User\Repository;

use App\Core\Entity\EntityRepositoryInterface;

interface UserRepositoryInterface extends EntityRepositoryInterface
{
    public function findByEmail(string $email): ?\stdClass;

}
