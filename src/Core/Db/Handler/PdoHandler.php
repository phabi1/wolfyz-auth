<?php

namespace App\Core\Db\Handler;

class PdoHandler {
    
    private $_pdo;
    private $_lastQuery;

    public $prefix;

    public function __construct($dsn, $username = null, $password = null, $options = [], ?string $prefix = null)
    {
        $this->_pdo = new \PDO($dsn, $username, $password, $options);
        if ($prefix) {
            $this->prefix = $prefix;
        }
    }

    public function escape($value) {
        if (is_int($value) || is_float($value)) {
            return $value;
        } else if (is_bool($value)) {
            return $value ? 1 : 0;
        }
        return '"' . addcslashes($value, "\0\n\r\\\"'\x1a") . '"';
    }

    public function fetchAll($sql)
    {
        $this->_lastQuery = $sql;
        $stmt = $this->_pdo->query($sql);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    public function fetchRow($sql)
    {
        $this->_lastQuery = $sql;
        $stmt = $this->_pdo->query($sql);
        return $stmt->fetch(\PDO::FETCH_OBJ);
    }

    public function fetchValue($sql)
    {
        $this->_lastQuery = $sql;
        $stmt = $this->_pdo->query($sql);
        $row = $stmt->fetchColumn(0);
        return $row;
    }

    public function execute($sql)
    {
        $this->_lastQuery = $sql;
        return $this->_pdo->exec($sql);
    }

    public function insertId() {
        return $this->_pdo->lastInsertId();
    }

    public function lastError()
    {
        return $this->_pdo->errorInfo();
    }

    public function lastQuery()
    {
        return $this->_lastQuery ?? null;
    }

}