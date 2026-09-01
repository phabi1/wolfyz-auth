<?php

namespace App\Core\Db\Exception;

class DbException extends \Exception
{
    private $query;

    public function __construct($message, $query = '')
    {
        parent::__construct($message);
        $this->query = $query;
    }

    public function getQuery()
    {
        return $this->query;
    }
}