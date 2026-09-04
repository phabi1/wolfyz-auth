<?php

namespace App\Core\Entity;

interface EntityServiceInterface
{
    public function getDefinition();
    
    public function items(array $filters = [], $page = 1, $size = 10, $sort = null, $order = 'asc', array $fields = [], string $search = '');

    public function item($id, array $fields = []);

    public function create($data);

    public function update($id, $data);

    public function delete($id);
}