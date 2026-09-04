<?php

namespace App\Core\Entity\Search;

use App\Core\Entity\EntityServiceInterface;

class DefaultSearch implements SearchInterface
{
    private $entityService;

    public function setEntityService(EntityServiceInterface $entityService)
    {
        $this->entityService = $entityService;
    }

    public function build($query, $search, array &$joins)
    {
        $definition = $this->entityService->getDefinition();

        if (!$definition->getSearch()) {
            return;
        }
        $fields = $definition->getSearch();
        $db = $this->entityService->getEntityManager()->getDb();

        $alias = $this->entityService->getAliasMap()->getAlias($definition->getName());

        $matches = [];
        foreach ($fields as $field) {
            if (strpos($field, '.') === false) {
                $alias = 'a';
                $query->where($db->expr()->like($alias . '.' . $field, '%' . $search . '%'));
            } else {
                list($relationName, $relationField) = explode('.', $field);
                if (!$definition->getRelations()->has($relationName)) {
                    continue;
                }
                $relation = $definition->getRelations()->get($relationName);
                $relationDefinition = $this->entityService->getEntityManager()->getEntityDefinition()->get($relation->getTargetEntity());
                $relationAlias = $this->entityService->getAliasMap()->getAlias($relation->getTargetEntity());
                if (!in_array($relationName, $joins)) {
                    $query->join($relationDefinition->getTable(), $relationAlias, $alias . '.' . $relation->getOption('join_field') . ' = ' . $relationAlias . '.id');
                    $joins[] = $relationName;
                }
                $matches[] = $relationAlias . '.' . $relationField;
            }
        }
        if (!empty($matches)) {
            $query->where($db->expr()->match($matches, $search));
        }
    }
}