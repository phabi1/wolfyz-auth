<?php

namespace App\Core\Db;

trait DbAwareTrait
{
    /**
     * Summary of db
     * @var Db
     */
    protected $db;

    public function setDb(Db $db)
    {
        $this->db = $db;
    }
}