<?php

namespace Pomm\Connection\FilterChain;

use Pomm\Exception\Exception as PommException;
use Pomm\Connection\Connection;

class QueryFilterChain
{
    protected $filters = array();
    protected $pointer;
    protected $connection;
    public $query;
    public $values;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function execute($query, $values = array())
    {
        $this->query    = $query;
        $this->values = $values;
        $this->pointer = 0;

        return $this->executeNext($this);

    }

    public function registerFilter(FilterInterface $filter)
    {
        array_unshift($this->filters, $filter);

        return $this;
    }

    public function executeNext()
    {
        if ($this->pointer >= count($this->filters))
        {
            throw new PommException(sprintf("Filter chain went over the last filter (position '%d').", $this->pointer));
        }

        return $this->filters[$this->pointer++]->execute($this);
    }

    public function getConnection()
    {
        return $this->connection;
    }
}
