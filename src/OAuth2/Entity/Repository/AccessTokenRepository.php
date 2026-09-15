<?php

namespace App\OAuth2\Entity\Repository;

use App\Core\Entity\EntityRepository;


class AccessTokenRepository extends EntityRepository implements AccessTokenRepositoryInterface
{
    public function findByToken(string $token): ?\stdClass
    {
        $qb = $this->db->createQuery();
        $qb->from($this->definition->getTable())
            ->where($this->db->expr()->eq('token', $token));
        $res = $this->db->row($qb);
        if ($res) {
            return $this->unserialize($res);
        }
        return null;
    }
}