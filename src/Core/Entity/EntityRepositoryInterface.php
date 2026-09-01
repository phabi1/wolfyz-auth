<?php

namespace App\Core\Entity;

interface EntityRepositoryInterface
{
    public function getDefinition();

    public function findById($id): \stdClass|null;

    public function findByIds(array $ids): array;

    public function find($filters = []): array;

    public function count($filters = []): int;

    public function findOne($filters = []): \stdClass|null;

    public function insert($data): \stdClass;

    public function update($id, $data): \stdClass;

    public function delete($id);

    public function deleteBy($filters = []);
}