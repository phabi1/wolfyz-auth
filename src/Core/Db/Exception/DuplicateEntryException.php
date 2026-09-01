<?php

namespace App\Core\Db\Exception;

class DuplicateEntryException extends DbException
{
    public function __construct(string $message = "", string $query = "")
    {
        parent::__construct($message, $query);
    }
}