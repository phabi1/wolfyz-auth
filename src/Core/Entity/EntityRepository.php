<?php

namespace App\Core\Entity;

use App\Core\Entity\Definition\Field;

class EntityRepository implements EntityRepositoryInterface
{
    protected $db;
    protected $definition;

    public function __construct($db, $definition)
    {
        $this->db = $db;
        $this->definition = $definition;
    }

    public function getDefinition()
    {
        return $this->definition; // Implementation for fetching the entity definition
    }

    public function findById($id): \stdClass|null
    {
        return $this->findOne(['id' => ['eq' => $id]]); // Implementation for fetching a single item by ID based on the definition
    }

    public function findByIds(array $ids): array
    {
        return $this->find(['id' => ['in' => $ids]]); // Implementation for fetching multiple items by an array of IDs based on the definition
    }

    public function findOne($filters = []): \stdClass|null
    {
        $options = [
            'limit' => 1
        ];
        $results = $this->find($filters, $options);
        return count($results) > 0 ? $results[0] : null;
    }

    public function find($filters = [], $options = []): array
    {
        $options = array_merge([
            'offset' => null,
            'limit' => null,
            'sort' => null,
            'order' => 'asc'
        ], $options);

        $sql = $this->db->createQuery()->from($this->definition['table']);

        // Apply filters to the query based on the provided filters and definition
        $this->applyFiltersToSql($filters, $sql);

        if ($options['sort']) {
            $sql->orderBy($options['sort'], $options['order']);
        }

        if ($options['limit'] !== null && $options['offset'] !== null) {
            $sql->range($options['limit'], $options['offset']);
        } else if ($options['limit'] !== null) {
            $sql->range($options['limit']);
        }

        $res = $this->db->rows($sql); // Implementation for fetching a list of items based on the definition
        return array_map(function ($row) {
            return $this->unserialize($row); // Here you can implement any transformation needed based on the definition
        }, $res);
    }

    public function count($filters = []): int
    {
        $sql = $this->db->createQuery()
            ->select('COUNT(*)', 'count')
            ->from($this->definition['table']);

        // Apply filters to the query based on the provided filters and definition
        $this->applyFiltersToSql($filters, $sql);

        return (int) $this->db->value($sql);
    }

    public function insert($obj): \stdClass
    {
        $data = $this->serialize($obj);

        if (isset($this->definition['fields']['created_at']) && !isset($data['created_at'])) {
            $data['created_at'] = date('Y-m-d H:i:s');
        }


        if (isset($this->definition['fields']['updated_at']) && !isset($data['updated_at'])) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }

        $id = $this->db->insert($this->definition['table'], $data); // Implementation for creating a new item based on the definition and provided data
        return $this->findById($id);
    }

    public function update($id, $obj): \stdClass
    {
        $data = $this->serialize($obj);

        if (isset($this->definition['fields']['updated_at']) && !isset($data['updated_at'])) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }

        $this->db->update($this->definition['table'], $data, ['id' => $id]); // Implementation for updating an existing item identified by ID with the provided data
        return $this->findById($id);
    }

    public function delete($id)
    {
        $this->db->delete($this->definition['table'], ['id' => $id]); // Implementation for deleting an item identified by ID
    }

    public function deleteBy($filters = [])
    {
        $result = $this->find($filters);
        foreach ($result as $item) {
            $this->db->delete($this->definition['table'], $item->id);
        }
    }

    protected function serialize($obj)
    {
        $data = [];
        if (isset($this->definition['fields'])) {
            foreach ($this->definition['fields'] as $field => $fieldDef) {
                if (isset($obj[$field])) {
                    $value = $obj[$field];
                    if ($fieldDef['type'] === Field::TYPE_ARRAY && $value) {
                        $value = implode(',', $value);
                    } else if ($fieldDef['type'] === Field::TYPE_JSON) {
                        $value = json_encode($value);
                    } else if ($fieldDef['type'] === Field::TYPE_DATETIME && $value) {
                        if (!is_int($value)) {
                            throw new \InvalidArgumentException("Expected integer timestamp for field '{$field}', got " . gettype($value));
                        }
                        $value = date('Y-m-d H:i:s', $value);
                    } else if ($fieldDef['type'] === Field::TYPE_DATE && $value) {
                        if (!is_int($value)) {
                            throw new \InvalidArgumentException("Expected integer timestamp for field '{$field}', got " . gettype($value));
                        }
                        $value = date('Y-m-d', $value);
                    } else if ($fieldDef['type'] === Field::TYPE_TIME && $value) {
                        if (!is_int($value)) {
                            throw new \InvalidArgumentException("Expected integer timestamp for field '{$field}', got " . gettype($value));
                        }
                        $value = date('H:i:s', $value);
                    } else if ($fieldDef['type'] === Field::TYPE_BOOLEAN) {
                        $value = $value ? 1 : 0;
                    } else if ($fieldDef['type'] === Field::TYPE_INTEGER) {
                        $value = (int) $value;
                    }
                    $data[$field] = $value;
                }
            }
        }
        return $data;
    }

    public function unserialize($data): \stdClass
    {
        $obj = $data;
        if (isset($this->definition['fields'])) {
            $obj = [];
            foreach ($this->definition['fields'] as $field => $fieldDef) {
                if (isset($data->$field)) {
                    $value = $data->$field;
                    if ($fieldDef['type'] === Field::TYPE_ARRAY) {
                        $value = $value ? explode(',', $value) : [];
                    } else if ($fieldDef['type'] === Field::TYPE_JSON && $value) {
                        $value = json_decode($value);
                    } else if ($fieldDef['type'] === Field::TYPE_DATETIME && $value) {
                        $value = strtotime($value);
                    } else if ($fieldDef['type'] === Field::TYPE_DATE && $value) {
                        list($year, $month, $day) = explode('-', $value);
                        $value = mktime(0, 0, 0, (int) $month, (int) $day, (int) $year);
                    } else if ($fieldDef['type'] === Field::TYPE_TIME && $value) {
                        $value = strtotime($value);
                    } else if ($fieldDef['type'] === Field::TYPE_INTEGER) {
                        $value = (int) $value;
                    } else if ($fieldDef['type'] === Field::TYPE_BOOLEAN) {
                        $value = (bool) $value;
                    }
                    $obj[$field] = $value;
                } else {
                    $obj[$field] = null;
                }
            }
        }
        // Here you can implement any transformation needed based on the definition before returning the item
        return (object) $obj;
    }

    protected function applyFiltersToSql($filters, $sql)
    {
        $where = [];

        foreach ($filters as $field => $conditions) {
            if (isset($this->definition['fields'][$field])) {
                foreach ($conditions as $operator => $value) {
                    switch ($operator) {
                        case 'eq':
                            $where[] = $this->db->expr()->eq($field, $value);
                            break;
                        case 'in':
                            $where[] = $this->db->expr()->in($field, $value);
                            break;
                        default:
                            throw new \Exception("Unsupported operator: {$operator}");
                    }
                }
            }
        }

        if ($where) {
            $sql->where($this->db->expr()->and($where));
        }
    }
}