<?php

namespace App\Core\Entity;

use App\Core\Db\Db;
use App\Core\Entity\Definition\Definition;

interface EntityRepositoryInterface
{
    public function setDb(Db $db): void;
    public function setDefinition(Definition $definition): void;
    public function getDefinition(): Definition;

    public function findById($id): \stdClass|null;

    public function findByIds(array $ids): array;

    public function find(array $filters = []): array;

    public function exists(array $filters = []): bool;

    public function count(array $filters = []): int;

    public function findOne(array $filters = []): \stdClass|null;

    public function insert($data): \stdClass;

    public function update($id, $data): \stdClass;

    public function delete($id);

    public function deleteBy($filters = []);

    public function beginTransaction(): void;

    public function commit(): void;

    public function rollback(): void;
}