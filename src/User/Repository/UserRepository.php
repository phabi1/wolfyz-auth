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

    /**
     * Users matching a free-text search on email, name, firstname and lastname.
     *
     * @param array<array{0: string, 1: string}> $sort [field, 'asc'|'desc'] pairs, already whitelisted
     */
    public function search(string $search = '', array $sort = [], ?int $limit = null, int $offset = 0): array
    {
        $qb = $this->db->createQuery()->select('*')->from('auth_user');
        $this->applySearch($qb, $search);
        foreach ($sort as [$field, $order]) {
            $qb->orderBy($this->db->quote($field), strtolower($order) === 'desc' ? 'DESC' : 'ASC');
        }
        $qb->orderBy($this->db->quote('id'), 'ASC');
        if ($limit !== null) {
            $qb->range($limit, $offset > 0 ? $offset : null);
        }

        return array_map(fn ($row) => $this->unserialize($row), $this->db->rows($qb));
    }

    public function countSearch(string $search = ''): int
    {
        $qb = $this->db->createQuery()->select('COUNT(*)', 'count')->from('auth_user');
        $this->applySearch($qb, $search);

        return (int) $this->db->value($qb);
    }

    private function applySearch($qb, string $search): void
    {
        $search = trim($search);
        if ($search === '') {
            return;
        }
        // Escape LIKE wildcards so that "%" and "_" are matched literally.
        $pattern = $this->db->escape('%' . addcslashes($search, '%_\\') . '%');
        $conditions = array_map(
            fn (string $field) => $this->db->quote($field) . ' LIKE ' . $pattern,
            ['email', 'name', 'firstname', 'lastname'],
        );
        $qb->where('(' . implode(' OR ', $conditions) . ')');
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
