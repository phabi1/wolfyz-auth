<?php

namespace App\User\Repository;

use App\Core\Entity\EntityRepository;

class UserRepository extends EntityRepository implements UserRepositoryInterface {

    public function findByEmail(string $email): \stdClass | null {
        $query = $this->db->createQuery();
        $query->from($this->definition['table'])
        ->where($this->db->expr()->eq('email', $email));

        $value = $this->db->row($query);
        return $value ?: null;
    }

    public function existsEmail(string $email,?int $exclude = null): bool {
        $query = $this->db->createQuery();
        $query->from($this->definition['table'])
        ->where($this->db->expr()->eq('email', $email))
        ->select('id');

        if ($exclude) {
            $query->where($this->db->expr()->ne('id', $exclude));
        }

        $value = $this->db->value($query);
        return $value ? true : false;
    }
}