<?php

namespace App\Core\Db;

interface DbAwareInterface
{
    public function setDb(Db $db);
}