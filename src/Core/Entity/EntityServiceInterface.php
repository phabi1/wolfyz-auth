<?php

namespace App\Core\Entity;

use App\Core\Entity\EntityManager;
interface EntityServiceInterface
{
    public function getEntityManager(): EntityManager;

    public function getAliasMap();

    public function getDefinition();

    public function items(array $filters = [], $page = 1, $size = 10, $sort = null, array $fields = [], string $search = '');

    public function item($id, array $fields = []);

    public function create($data);

    public function update($id, $data);

    public function delete($id);
}