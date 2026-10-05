<?php

namespace App\User\Repository;

use App\Core\Db\Db;
use App\Core\Entity\EntityRepository;
use App\User\Password\PasswordEncoder;

class UserRepository extends EntityRepository implements UserRepositoryInterface
{
    private PasswordEncoder $passwordEncoder;

    public function __construct(PasswordEncoder $passwordEncoder)
    {
        $this->passwordEncoder = $passwordEncoder;
    }

    public function findByEmail(string $email): ?\stdClass
    {
        $qb = $this->db->createQuery();
        $qb->select('*')
            ->from('auth_user')
            ->where($this->db->expr()->eq('email', $email));

        return $this->db->row($qb) ?: null;
    }

    public function insert($data): \stdClass
    {
        $data['password_hash'] = $this->passwordEncoder->hash($data['password_hash']);
        return parent::insert($data);
    }

    public function update($id, $data): \stdClass
    {
        if (isset($data['password_hash'])) {
            $data['password_hash'] = $this->passwordEncoder->hash($data['password_hash']);
        }
        return parent::update($id, $data);
    }
}
