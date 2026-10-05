<?php
namespace App\Core\Watchdog\Storage;

use App\Core\Db\Db;

class DbStorage implements StorageInterface
{
    private Db $db;
    
    public function __construct(Db $db)
    {
        $this->db = $db;
    }
    
    public function log(string $message, array $data = [], string $level = 'info')
    {
        $this->db->insert('wolf_watchdog_log', [
            'message' => $message,
            'data' => json_encode($data),
            'level' => $level,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
}