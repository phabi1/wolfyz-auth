<?php

namespace App\Core\Mvc\Controller;

use Symfony\Component\HttpFoundation\Request;
use App\Core\Entity\EntityService;

class EntityController extends ApiController
{
    protected $identifierName = 'id';

    protected $entityName;

    protected $entityService;

    protected $usePagination = true;

    public function itemsAction(Request $request)
    {
        $filters = $this->buildFilters($request);


        $sort = $request->query->get('sort');
        $order = $request->query->get('order') === 'desc' ? 'desc' : 'asc';
        $fields = $this->extractFieldsFromRequest($request);

        $entityService = $this->getEntityService();

        if ($this->usePagination) {
            $page = max(1, (int) ($request->query->get('page') ?? 1));
            $size = min(100, max(1, (int) ($request->query->get('size') ?? 10)));
        } else {
            $page = null;
            $size = null;
        }

        $entities = $entityService->items($filters, $page, $size, $sort, $order, $fields);

        $total = $entityService->count($filters);

        return [
            'items' => $this->serializeCollection($entities),
            'total' => $total,
            'page' => $page,
            'size' => $size
        ];
    }

    public function itemAction(Request $request)
    {
        $id = $this->getIdentifierValue($request);
        $fields = $this->extractFieldsFromRequest($request);

        $entity = $this->getEntityService()->item($id, $fields);

        if (!$entity) {
            throw new \Exception("Entity with ID {$id} not found.");
        }
        $item = $this->serializeItem($entity);

        return $item;
    }

    public function createAction(Request $request)
    {
        $body = $request->getPayload()->all();
        $data = $this->prepareDataFromRequest($body, $request);
        $fields = $this->extractFieldsFromRequest($request);

        $entity = $this->getEntityService()->create($data);
        return $this->serializeItem($entity);
    }

    public function updateAction(Request $request)
    {
        $id = $this->getIdentifierValue($request);
        $fields = $this->extractFieldsFromRequest($request);

        $body = $request->getPayload()->all();
        $data = $this->prepareDataFromRequest($body, $request);

        $entity = $this->getEntityService()->update($id, $data);
        return $this->serializeItem($entity);
    }

    public function deleteAction(Request $request)
    {
        $id = $this->getIdentifierValue($request);
        $this->getEntityService()->delete($id);
        return ['success' => true];
    }

    public function bulkCreateAction(Request $request)
    {
        $body = $request->getPayload()->all();
        $createdItems = [];
        foreach ($body as $itemData) {
            $data = $this->prepareDataFromRequest($itemData, $request);
            $createdItems[] = $this->getEntityService()->create($data);
        }
        $fields = $this->extractFieldsFromRequest($request);
        return $this->serializeCollection($createdItems);
    }

    public function bulkUpdateAction(Request $request)
    {
        $body = $request->getPayload()->all();
        $updatedItems = [];
        foreach ($body as $itemData) {
            if (!isset($itemData[$this->identifierName])) {
                continue; // Skip items without identifier
            }
            $id = $itemData[$this->identifierName];
            $data = $this->prepareDataFromRequest($itemData, $request);
            $updatedItems[] = $this->getEntityService()->update($id, $data);
        }
        $fields = $this->extractFieldsFromRequest($request);
        return $this->serializeCollection($updatedItems);
    }

    public function bulkDeleteAction(Request $request)
    {
        $body = $request->getPayload()->all();
        foreach ($body as $id) {
            $this->getEntityService()->delete($id);
        }
        return ['success' => true];
    }

    public function permissionsCheck(Request $request)
    {
        // By default, allow all actions. This can be overridden in child controllers for specific permission checks.
        return true;
    }

    protected function getIdentifierValue($request)
    {
        return $request->attributes->get($this->identifierName);
    }

    protected function getEntityService()
    {
        if (!$this->entityService) {
            $this->entityService = $this->buildEntityService();

        }
        return $this->entityService;
    }

    protected function buildEntityService()
    {
        $entityManager = $this->getService('entity.manager');
        $service = new EntityService($entityManager);
        $service->setEntityName($this->entityName);
        return $service;
    }

    protected function buildFilters($request)
    {
        $queryParams = $request->query->get('filters');
        if (!$queryParams) {
            return [];
        }
        $segments = explode(';', $queryParams);
        $filters = [];
        foreach ($segments as $segment) {
            $parts = explode(':', $segment);
            $count = count($parts);
            if ($count === 2) {
                $filters[$parts[0]]['eq'] = $parts[1];
            } elseif ($count === 3) {
                $filters[$parts[0]][$parts[1]] = $parts[2];
            } else {
                throw new \Exception("Invalid filter format: {$segment}");
            }
        }
        return $filters;
    }

    /**
     * Prepares data for create/update actions based on the request. This method can be overridden in the child controller to customize data preparation.
     * @param array $body
     * @param Request $request
     * @return array
     */
    protected function prepareDataFromRequest(array $body, Request $request)
    {
        $data = [];
        $definition = $this->getEntityService()->getDefinition();
        foreach ($definition['fields'] as $name => $field) {
            if ($name === $this->identifierName) {
                continue; // Skip identifier field
            }
            if ($field['readonly'] ?? false) {
                continue; // Skip readonly fields
            }
            if (isset($body[$name])) {
                $data[$name] = $body[$name];
            }
        }
        return $data;
    }

    protected function serializeCollection($entities)
    {
        // This method can be overridden in the child controller to customize collection serialization
        return array_map(function ($entity) {
            return $this->serializeItem($entity);
        }, $entities);
    }

    protected function serializeItem($entity)
    {
        $data = [];
        $definition = $this->getEntityService()->getDefinition();
        foreach ($definition['fields'] as $name => $field) {
            if (isset($entity->$name)) {
                $data[$name] = $entity->$name;
            }
        }
        if ($definition->hasRelations()) {
            foreach ($definition->getRelations() as $relation) {
                $name = $relation->getName();
                if (isset($entity->{$name})) {
                    $data[$name] = $entity->{$name};
                }
            }
        }

        return $data;
    }

    private function extractFieldsFromRequest($request)
    {
        $fieldsParam = $request->query->get('fields');
        if (!$fieldsParam) {
            return [];
        }
        return array_filter(array_map('trim', explode(',', $fieldsParam)), function ($field) {
            return !empty($field);
        });
    }

}