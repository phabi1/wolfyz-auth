<?php

namespace App\Core\Db;

use App\Core\Db\Exception\DuplicateEntryException;
use App\Core\Db\Exception\DbException;

class Db
{
    private $_handler;

    private $_expr;

    public function __construct($handler)
    {
        $this->_handler = $handler;
    }

    public function getHandler()
    {
        return $this->_handler;
    }

    public function createQuery()
    {
        return new Query($this->_handler);
    }

    public function expr()
    {
        if (!$this->_expr) {
            $this->_expr = new Expr();
            $this->_expr->setDb($this);
        }
        return $this->_expr;
    }

    public function escape(mixed $value): mixed
    {
        if (is_string($value)) {
            return $this->_handler->escape($value);
        }
        return $value;
    }

    public function quote(string $field): string {
        return '`'.$field.'`';
    }

    public function beginTransaction()
    {
        $this->_handler->query('START TRANSACTION');
    }

    public function commit()
    {
        $this->_handler->query('COMMIT');
    }

    public function rollback()
    {
        $this->_handler->query('ROLLBACK');
    }

    public function rows($sql)
    {
        if ($sql instanceof Query) {
            $sql = $sql->build();
        }
        $res = $this->_handler->fetchAll($sql);
        return $res;
    }

    public function row($sql)
    {
        if ($sql instanceof Query) {
            $sql = $sql->build();
        }
        $res = $this->_handler->fetchRow($sql);
        return $res;
    }

    public function value($sql)
    {
        if ($sql instanceof Query) {
            $sql = $sql->build();
        }
        $res = $this->_handler->fetchValue($sql);
        return $res;
    }

    public function insert($table, $data)
    {
        $keys = array_map(function($field) {
            return $this->quote($field);
        }, array_keys($data));

        $values = array_map(function ($value) {
            return $this->escape($value);
        }, array_values($data));

        $sql = 'INSERT INTO ' . $this->quote($this->_handler->prefix . $table). '('.implode(',', $keys).') VALUES ('.implode(',', $values).')';
        
        $res = $this->_handler->execute($sql);
        if ($res === false) {
            $this->handleError();
        }
        return $this->_handler->insertId();
    }

    public function update($table, $data, array $where)
    {
        $res = $this->_handler->update($this->_handler->prefix . $table, $data, $where);
        if ($res === false) {
            $this->handleError();
        }
        return $res;
    }

    public function delete($table, array $where)
    {
        $res = $this->_handler->delete($this->_handler->prefix . $table, $where);
        if ($res === false) {
            $this->handleError();
        }
        return $res;
    }

    private function handleError()
    {
        $error = $this->_handler->lastError();
        $message = $error[2] ?? null;
        if (!$message) {
            $message = 'Unknown database error.';
        }
        if (str_contains($message, 'Duplicate entry')) {
            throw new DuplicateEntryException($message, $this->_handler->last_query);
        }
        throw new DbException($message, $this->_handler->lastQuery());
    }

}