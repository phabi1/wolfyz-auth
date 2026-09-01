<?php

namespace App\Core\Entity;

use App\Core\Entity\Definition\Relation;

class EntityService implements EntityServiceInterface
{
    private $entityName;

    private $entityManager;

    private $repository;

    private $aliasMap;

    public function __construct(EntityManager $entityManager)
    {
        $this->aliasMap = new AliasMapper();
        $this->setEntityManager($entityManager);
    }

    public function setEntityManager(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
        return $this;
    }

    public function setEntityName($entityName)
    {
        $this->entityName = $entityName;
        return $this;
    }

    public function getRepository(): EntityRepositoryInterface
    {
        if (!$this->repository) {
            if (!$this->entityName) {
                throw new \Exception("Entity name not set for " . get_class($this));
            }
            $this->repository = $this->entityManager->getRepository($this->entityName);
        }
        return $this->repository;
    }

    public function getDefinition()
    {
        return $this->getRepository()->getDefinition(); // Implementation for fetching the entity definition from the repository
    }

    public function items($filters = [], $page = 1, $size = 10, $sort = null, $order = 'asc', array $fields = [])
    {
        $options = [
            'offset' => ($page - 1) * $size,
            'limit' => $size,
            'sort' => $sort,
            'order' => $order
        ];


        $db = $this->entityManager->getDb();
        $query = $db->createQuery();
        $definition = $this->getDefinition();

        $alias = $this->aliasMap->getAlias($this->entityName);
        $query->from($definition['table'], $alias);


        if ($filters) {
            foreach ($filters as $field => $condition) {
                $fieldDef = $definition['fields'][$field] ?? null;
                if (!$fieldDef) {
                    continue; // Skip unknown fields
                }

                $this->applyFilterToSql($query, $field, $condition, $alias);
            }
        }

        if ($this->hasRelations($definition)) {
            foreach ($definition['relations'] as $name => $relation) {
                $relationDefinition = $this->entityManager->getRepository($relation['target_entity'])->getDefinition();
                $relationAlias = $this->aliasMap->getAlias($relation['target_entity']);

                $match = false;

                foreach ($relationDefinition['fields'] as $fieldName => $fieldDef) {
                    $filterKey = $name . '.' . $fieldName;
                    if (isset($filters[$filterKey])) {
                        $this->applyFilterToSql($query, $fieldName, $filters[$filterKey], $relationAlias);
                        $match = true;
                    }
                }

                if ($match) {
                    $query->join($relationDefinition['table'], $relationAlias, $alias . '.' . $relation['options']['join_field'] . ' = ' . $relationAlias . '.id');
                }
            }
        }

        $query->select($alias . '.id');

        if ($options['sort']) {
            $query->orderBy($alias . '.' . $options['sort'], $options['order']);
        }

        if ($options['limit'] !== null && $options['offset'] !== null) {
            $query->range($options['limit'], $options['offset']);
        } else if ($options['limit'] !== null) {
            $query->range($options['limit']);
        }

        $result = $db->rows($query);

        $ids = array_column($result, 'id');

        return $this->loadByIds($ids, $fields); // Implementation for fetching a list of items based on the definition, provided filters, pagination, and sorting
    }

    public function item($id, array $fields = [])
    {
        $result = $this->loadByIds([$id], $fields);
        return count($result) > 0 ? $result[0] : null;
    }

    public function count($filters = [])
    {
        $db = $this->entityManager->getDb();
        $query = $db->createQuery();
        $definition = $this->getDefinition();

        $alias = $this->aliasMap->getAlias($this->entityName);
        $query->from($definition['table'], $alias);


        if ($filters) {
            foreach ($filters as $field => $condition) {
                $fieldDef = $definition['fields'][$field] ?? null;
                if (!$fieldDef) {
                    continue; // Skip unknown fields
                }

                $this->applyFilterToSql($query, $field, $condition, $alias);
            }
        }

        if ($this->hasRelations($definition)) {
            foreach ($definition['relations'] as $name => $relation) {
                $relationDefinition = $this->entityManager->getRepository($relation['target_entity'])->getDefinition();
                $relationAlias = $this->aliasMap->getAlias($relation['target_entity']);

                $match = false;

                foreach ($relationDefinition['fields'] as $fieldName => $fieldDef) {
                    $filterKey = $name . '.' . $fieldName;
                    if (isset($filters[$filterKey])) {
                        $this->applyFilterToSql($query, $fieldName, $filters[$filterKey], $relationAlias);
                        $match = true;
                    }
                }

                if ($match) {
                    $query->join($relationDefinition['table'], $relationAlias, $alias . '.' . $relation['options']['join_field'] . ' = ' . $relationAlias . '.id');
                }
            }
        }

        $query->select($alias . '.id');

        $rootQuery = $db->createQuery()->from($definition['table'], $alias);
        $rootQuery->where($db->expr()->in($alias . '.id', $query));
        $rootQuery->select('COUNT(*)', 'count');

        return (int) $db->value($rootQuery);
    }

    public function create($data)
    {
        $entity = $this->getRepository()->insert($data); // Implementation for creating a new item based on the definition and provided data
        return $this->loadById($entity->id); // Fetch the created entity with relations loaded
    }

    public function update($id, $data)
    {
        $this->getRepository()->update($id, $data); // Implementation for updating an existing item by ID based on the definition and provided data
        return $this->loadById($id); // Fetch the updated entity with relations loaded
    }

    public function delete($id)
    {
        return $this->getRepository()->delete($id); // Implementation for deleting an existing item by ID based on the definition
    }

    protected function applyFilterToSql($query, $field, $condition, $alias = null)
    {
        $db = $this->entityManager->getDb();
        $fieldName = $alias ? $alias . '.' . $field : $field;
        if (\is_array($condition)) {
            foreach ($condition as $operator => $value) {
                switch ($operator) {
                    case 'eq':
                        $query->where($db->expr()->eq($fieldName, $value));
                        break;
                    case 'neq':
                        $query->where($db->expr()->neq($fieldName, $value));
                        break;
                    case 'gt':
                        $query->where($db->expr()->gt($fieldName, $value));
                        break;
                    case 'gte':
                        $query->where($db->expr()->gte($fieldName, $value));
                        break;
                    case 'lt':
                        $query->where($db->expr()->lt($fieldName, $value));
                        break;
                    case 'lte':
                        $query->where($db->expr()->lte($fieldName, $value));
                        break;
                    case 'in':
                        if (is_array($value) && !empty($value)) {
                            $query->where($db->expr()->in($fieldName, $value));
                        }
                        break;
                    case 'nin':
                        if (is_array($value) && !empty($value)) {
                            $query->where($db->expr()->nin($fieldName, $value));
                        }
                        break;
                    case 'like':
                        $query->where($db->expr()->like($fieldName, '%' . $value . '%'));
                        break;
                    case 'nlike':
                        $query->where($db->expr()->nlike($fieldName, '%' . $value . '%'));
                        break;
                }
            }
        } else {
            // Default to equality if condition is not an array
            $query->where($db->expr()->eq($fieldName, $condition));
        }
    }

    protected function loadById($id, array $fields = [])
    {
        $result = $this->loadByIds([$id], $fields);
        return count($result) > 0 ? $result[0] : null;
    }

    /**
     * Load entities by ids with optional fields and relations.
     * @param mixed $ids
     * @param array $fields
     * @return array
     */
    protected function loadByIds($ids, array $fields = [])
    {
        if (empty($ids)) {
            return [];
        }

        $byPassFields = empty($fields);

        $db = $this->entityManager->getDb();
        $query = $db->createQuery();
        $definition = $this->getDefinition();
        $alias = $this->aliasMap->getAlias($this->entityName);
        $query->from($definition['table'], $alias);

        foreach ($definition['fields'] as $fieldName => $fieldDef) {
            if (!$byPassFields && !in_array($fieldName, $fields)) {
                continue; // Skip fields not in the requested list
            }
            $query->select($alias . '.' . $fieldName);
        }

        $query->where($db->expr()->in($alias . '.id', $ids));

        $relationFields = [];

        if ($this->hasRelations($definition)) {
            foreach ($definition['relations'] as $name => $relation) {

                if (!$byPassFields && !in_array($name, $fields)) {
                    continue; // Skip relations not in the requested list
                }

                $relationFields[] = $name;
                $relationDefinition = $this->entityManager->getRepository($relation['target_entity'])->getDefinition();
                $relationAlias = $this->aliasMap->getAlias($relation['target_entity']);

                if ($relation['type'] === Relation::TYPE_ONE_TO_ONE) {
                    $query->leftJoin($relationDefinition['table'], $relationAlias, $alias . '.' . $relation['options']['join_field'] . ' = ' . $relationAlias . '.id');
                } else if ($relation['type'] === Relation::TYPE_ONE_TO_MANY) {
                    $query->leftJoin($relationDefinition['table'], $relationAlias, $relationAlias . '.' . $relation['options']['join_field'] . ' = ' . $alias . '.id');
                }

                $query->select('GROUP_CONCAT(' . $relationAlias . '.id) as ' . $name . '_ids');
            }
        }
        $query->groupBy($alias . '.id');

        $result = $db->rows($query);
        $items = array_reduce($result, function ($carry, $item) {
            $carry[$item->id] = $item;
            return $carry;
        }, []);

        $rows = [];
        foreach ($ids as $id) {
            $line = $items[$id] ?? null;
            if (!$line) {
                continue; // Skip if the item is not found
            }
            $row = $this->getRepository()->unserialize($line);
            foreach ($relationFields as $relationField) {
                $row->{$relationField . '_ids'} = $line->{$relationField . '_ids'};
            }
            $rows[] = $row;

        }

        $this->loadRelations($rows, $definition);

        return $rows; // Implementation for fetching a list of items by their IDs based on the definition
    }

    private function hasRelations($definition)
    {
        return isset($definition['relations']) && is_array($definition['relations']) && !empty($definition['relations']);
    }

    private function loadRelations(&$items, $definition)
    {
        if (!$this->hasRelations($definition)) {
            return;
        }
        foreach ($definition['relations'] as $name => $relation) {
            $ids = [];
            $prop = $name . '_ids';
            $relationType = $definition['relations'][$name]['type'];

            foreach ($items as $item) {
                if (!empty($item->$prop)) {
                    array_push($ids, ...explode(',', $item->$prop));
                }
            }

            $ids = array_unique($ids);
            if (empty($ids)) {
                continue;
            }


            $relatedItems = $this->entityManager->getRepository($relation['target_entity'])->findByIds($ids);
            $relatedItemsById = [];
            foreach ($relatedItems as $relatedItem) {
                $relatedItemsById[$relatedItem->id] = $relatedItem;
            }

            foreach ($items as $item) {
                if (empty($item->$prop)) {
                    continue;
                }
                $relatedIds = array_unique(explode(',', $item->$prop));
                if ($relationType === Relation::TYPE_ONE_TO_ONE) {
                    $relatedId = $relatedIds[0] ?? null;
                    $item->$name = isset($relatedItemsById[$relatedId]) ? $relatedItemsById[$relatedId] : null;
                } else if ($relationType === Relation::TYPE_ONE_TO_MANY) {
                    $item->{$name} = array_map(function ($relatedId) use ($relatedItemsById) {
                        return $relatedItemsById[$relatedId] ?? null;
                    }, $relatedIds);
                }
            }
        }
    }
}