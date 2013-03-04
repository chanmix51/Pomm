<?php

namespace Pomm\FilterChain;

use Pomm\Exception\Exception;
use Pomm\FilterChain\FilterInterface;
use Pomm\Connection\Connection;
use Pomm\Object\BaseObjectMap;

class QueryFilterChain
{
    protected $filters = array();
    protected $pointer;
    protected $sql;
    protected $values;
    protected $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function execute(BaseObjectMap $map, $sql, $values = array())
    {
        $this->sql    = $sql;
        $this->values = $values;
        $this->map    = $map;

        $this->pointer = 0;

        return $this->executeNext($this);

    }

    public function registerFilter(FilterInterface $filter)
    {
        array_unshift($this->filters, $filter);
    }

    public function executeNext()
    {
        if ($this->pointer >= count($this->filters)) {
            throw new Exception(sprintf("Filter chain went over the last filter (position '%d').", $this->pointer));
        }

        return $this->filters[$this->pointer++]->execute($this);
    }

    public function getSql()
    {
        return $this->sql;
    }

    public function getValues()
    {
        return $this->values;
    }

    public function getMap()
    {
        return $this->map;
    }

    public function getConnection()
    {
        return $this->connection;
    }
}
