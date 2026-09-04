<?php
namespace App\Core\Entity\Search;

use App\Core\Entity\EntityServiceInterface;

interface SearchInterface
{
    public function setEntityService(EntityServiceInterface $entityServiceInterface);
    public function build($query, string $search, array &$joins);
}