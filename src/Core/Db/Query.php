<?php

namespace App\Core\Db;

class Query
{
    private $_handler;

    private $_from;

    private $_where = [];

    private $_select = [];

    private $_joins = [];

    private $_having = [];

    private $_limit;

    private $_offset;

    private $_order = [];

    private $_groupBy = [];

    public function __construct($handler)
    {
        $this->_handler = $handler;
    }

    public function select($field, $alias = null)
    {
        if ($alias) {
            $this->_select[] = $field . ' AS ' . $alias;
        } else {
            $this->_select[] = $field;
        }
        return $this;
    }

    public function from($table, $alias = null)
    {
        $this->_from = ($table) . ($alias ? ' AS ' . $alias : '');
        return $this;
    }

    public function join($table, $alias, $condition)
    {
        $this->_join('JOIN', $table, $alias, $condition);
        return $this;
    }

    public function innerJoin($table, $alias, $condition)
    {
        $this->_join('INNER JOIN', $table, $alias, $condition);
        return $this;
    }

    public function leftJoin($table, $alias, $condition)
    {
        $this->_join('LEFT JOIN', $table, $alias, $condition);
        return $this;
    }

    public function rightJoin($table, $alias, $condition)
    {
        $this->_join('RIGHT JOIN', $table, $alias, $condition);
        return $this;
    }

    private function _join($type, $table, $alias, $condition)
    {
        $this->_joins[] = $type . ' ' . $this->_handler->prefix . ($table) . ' AS ' . $alias . ' ON ' . $condition;
        return $this;
    }

    public function having($having)
    {
        $this->_having[] = $having;
        return $this;
    }

    public function where($where)
    {
        $this->_where[] = $where;
        return $this;
    }

    public function range($limit, $offset = null)
    {
        $this->_limit = $limit;
        if ($offset) {
            $this->_offset = $offset;
        }
        return $this;
    }

    public function orderBy($field, $direction = 'ASC')
    {
        $this->_order[] = $field . ' ' . $direction;
        return $this;
    }

    public function groupBy($field)
    {
        $this->_groupBy[] = $field;
        return $this;
    }

    public function build()
    {
        $sql = 'SELECT ' . (!empty($this->_select) ? implode(', ', $this->_select) : '*') . ' FROM ' . $this->_from;
        if (!empty($this->_joins)) {
            $sql .= ' ' . implode(' ', $this->_joins);
        }
        if (!empty($this->_where)) {
            $sql .= ' WHERE ' . implode(' AND ', array_map(function ($w) {
                if ($w instanceof Expr\ExprInterface) {
                    return $w->build();
                }
                return (string) $w;
            }, $this->_where));
        }

        if (!empty($this->_groupBy)) {
            $sql .= ' GROUP BY ' . implode(', ', $this->_groupBy);
        }
        
        if (!empty($this->_having)) {
            $sql .= ' HAVING ' . implode(' AND ', array_map(function ($h) {
                if ($h instanceof Expr\ExprInterface) {
                    return $h->build();
                }
                return (string) $h;
            }, $this->_having));
        }

        if (!empty($this->_order)) {
            $sql .= ' ORDER BY ' . implode(', ', $this->_order);
        }

        if ($this->_limit) {
            $sql .= ' LIMIT ' . $this->_limit;
            if ($this->_offset) {
                $sql .= ' OFFSET ' . $this->_offset;
            }
        }
        return $sql;
    }

    public function __toString()
    {
        return $this->build();
    }
}