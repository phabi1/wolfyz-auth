<?php

namespace App\Core\Config;

use App\Core\Db\Db;
use App\Core\Db\Query;

class Settings
{
    private $settings = [];

    private $db;

    private $table = 'settings';

    public function __construct(Db $db, $table = 'settings')
    {
        $this->db = $db;
        $this->table = $table;
    }

    public function get($key, $default = null)
    {
        if (!array_key_exists($key, $this->settings)) {
            $query = $this->db->createQuery();
            $query->select('value')
                  ->from($this->table)
                  ->where($this->db->expr()->eq('name', $this->db->escape($key)));
            $value = $this->db->value($query);
            $this->settings[$key] = $value;
        }
        return $this->settings[$key];
    }

    public function set($key, $value)
    {
        $this->db->update($this->table, ['name' => $key, 'value' => $value], ['name' => $this->db->escape($key)]);
        $this->settings[$key] = $value;
    }
}