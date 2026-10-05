<?php

namespace App\Core\Entity;

use App\Core\Db\Db;
use App\Core\Db\Exception\DuplicateEntryException;
use App\Core\Entity\Definition\Definition;
use App\Core\Entity\Definition\Field;
use App\Core\Entity\Exception\DuplicateEntityException;

class EntityRepository implements EntityRepositoryInterface
{
    protected Db $db;
    protected Definition $definition;

    public function setDb(Db $db): void
    {
        $this->db = $db;
    }

    public function setDefinition(Definition $definition): void
    {
        $this->definition = $definition;
    }

    public function getDefinition(): Definition
    {
        return $this->definition; // Implementation for fetching the entity definition
    }

    public function findById($id, array $options = []): \stdClass|null
    {
        $primaryKeys = $this->getDefinition()->getPrimaryKeys();
        if (count($primaryKeys) !== 1) {
            throw new \InvalidArgumentException('findByIds only supports entities with a single primary key.');
        }
        $condition = [$primaryKeys[0] => ['eq' => $id]];
        return $this->findOne($condition, $options); // Implementation for fetching a single item by ID based on the definition
    }

    public function findByIds(array $ids, array $options = []): array
    {
        $primaryKeys = $this->getDefinition()->getPrimaryKeys();
        if (count($primaryKeys) !== 1) {
            throw new \InvalidArgumentException('findByIds only supports entities with a single primary key.');
        }
        return $this->find([$primaryKeys[0] => ['in' => $ids]], $options); // Implementation for fetching multiple items by an array of IDs based on the definition
    }

    public function findOne($filters = [], array $options = []): \stdClass|null
    {
        $options = array_merge([
            'limit' => 1
        ], $options);
        $results = $this->find($filters, $options);
        return count($results) > 0 ? $results[0] : null;
    }

    public function find($filters = [], array $options = []): array
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

    /**
     * Check if a record exists matching the filters
     * @param array $filters
     * @return bool Return true if at least one record matches the filters, false otherwise
     */
    public function exists(array $filters = []): bool
    {
        return $this->count($filters) > 0;
    }

    /**
     * Count the number of records matching the filters
     * @param array $filters
     * @return int
     */
    public function count(array $filters = []): int
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

        foreach ($this->definition->getFields() as $field) {
            if ($field->isNullable() && !isset($data[$field->getName()])) {
                $data[$field->getName()] = null;
            }
        }

        if ($this->definition->isTimestampable()) {
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');
        }

        try {
            $id = $this->db->insert($this->definition['table'], $data); // Implementation for creating a new item based on the definition and provided data
            if ($this->definition->isAutoIncrement() && !$id) {
                throw new \Exception('Failed to insert entity and retrieve auto-incremented ID');
            }
            if (!$this->definition->isAutoIncrement()) {
                $id = $data['id'] ?? null;
            }
        } catch (DuplicateEntryException $e) {
            throw new DuplicateEntityException($this->getDefinition()->getName());
        }
        return $this->findById($id);
    }

    public function update($id, $obj): \stdClass
    {
        $data = $this->serialize($obj);

        if ($this->definition->isTimestampable()) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }

        $this->db->update($this->definition['table'], $data, $this->db->expr()->eq('id', $id)); // Implementation for updating an existing item identified by ID with the provided data
        return $this->findById($id);
    }

    public function delete($id)
    {
        $this->db->delete($this->definition['table'], $this->db->expr()->eq('id', $id)); // Implementation for deleting an item identified by ID
    }

    public function deleteBy($filters = [])
    {
        $result = $this->find($filters);
        foreach ($result as $item) {
            $this->db->delete($this->definition['table'], $this->db->expr()->eq('id', $item->id));
        }
    }

    protected function serialize($obj)
    {
        $data = [];
        if (isset($this->definition['fields'])) {
            foreach ($this->definition['fields'] as $field => $fieldDef) {
                if (isset($obj[$field])) {
                    $value = $obj[$field];
                    if ($fieldDef['type'] === Field::TYPE_ARRAY) {
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
                        case 'lt':
                            $where[] = $this->db->expr()->lt([$field, $value], false);
                            break;
                        case 'lte':
                            $where[] = $this->db->expr()->lt([$field, $value], true);
                            break;
                        case 'gt':
                            $where[] = $this->db->expr()->gt([$field, $value], false);
                            break;
                        case 'gte':
                            $where[] = $this->db->expr()->gt([$field, $value], true);
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

    public function beginTransaction(): void
    {
        $this->db->beginTransaction();
    }

    public function commit(): void
    {
        $this->db->commit();
    }

    public function rollback(): void
    {
        $this->db->rollback();
    }
}