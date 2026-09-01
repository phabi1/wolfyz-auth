<?php

namespace App\User\Repository;

use App\Core\Entity\EntityRepositoryInterface;

interface UserRepositoryInterface extends EntityRepositoryInterface {
    function findByEmail(string $email): \stdClass | null;
    function existsEmail(string $email): bool;
}