<?php

namespace App\Core\Entity\Exception;

class EntityException extends \RuntimeException
{
    private $entitName;

    public function __construct(string $entitName, string $message = "", int $code = 0, \Throwable $previous = null)
    {
        $this->entitName = $entitName;
        parent::__construct($message, $code, $previous);
    }

    public function getEntityName(): string
    {
        return $this->entitName;
    }
}