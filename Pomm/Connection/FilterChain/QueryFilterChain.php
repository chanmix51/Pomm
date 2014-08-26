<?php

namespace Pomm\Connection\FilterChain;

use Pomm\Exception\Exception as PommException;
use Pomm\Connection\Connection;

/**
 * Pomm\Connection\FilterChain\QueryFilterChain
 *
 * Query filter chain
 * Allow treatments to be processes before and after query.
 * The filter chains input a SQL query as a string and an array of parameters. 
 * It is expected to output a resource produced by pg_execute which lies in the 
 * QueryFilter.
 *
 * @package Pomm
 * @version $id$
 * @copyright 2011 - 2014 Grégoire HUBERT
 * @author Grégoire HUBERT <hubert.greg@gmail.com>
 * @license X11 {@link http://opensource.org/licenses/mit-license.php}
 */
class QueryFilterChain
{
    protected $filters = array();
    protected $pointer;
    protected $connection;
    public $query;
    public $values;

    /**
     * __construct
     *
     * Create a new Query Filter Chain.
     *
     * @access public
     */
    public function __construct(Connection $connection, $parameters = array())
    {
        $this->connection = $connection;
    }

    /**
     * execute
     *
     * Launch the filters execution.
     *
     * @param String the SQL query
     * @param Array values for query's parameters
     * @return Resource the query result resource
     */
    public function execute($query, $values = array())
    {
        $this->query    = $query;
        $this->values = $values;
        $this->pointer = 0;

        return $this->executeNext($this);

    }

    /**
     * registerFilter
     *
     * Register a new FilterInterface to the filter chain.
     *
     * @param FilterInterface filter
     * @return QueryFilterChain
     */
    public function registerFilter(FilterInterface $filter)
    {
        array_unshift($this->filters, $filter);

        return $this;
    }

    /**
     * executeNext 
     *
     * execute the next filter in the filter chain
     *
     * @return Mixed
     */
    public function executeNext()
    {
        if ($this->pointer >= count($this->filters))
        {
            throw new PommException(sprintf("Filter chain went over the last filter (position '%d').\nHere is the filter chain definition:\n%s", $this->pointer, print_r($this->dumpDefinition(), true)));
        }

        return $this->filters[$this->pointer++]->execute($this);
    }

    /**
     * getConnection
     *
     * Returns the connection.
     *
     * @return Connection
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * dumpDefinition
     *
     * Dump a list as an array of filter classes in the order of execution.
     *
     * @return Array filters definition
     */
    public function dumpDefinition()
    {
        $definition = array();

        foreach ($this->filters as $index => $filter)
        {
            $definition[] = get_class($filter);
        }

        return $definition;
    }

    /**
     * insertFilter
     *
     * Insert a new filter between two existing filters.
     *
     * @param FilterInterface the filter to be inserted
     * @param Integer the position of the insertion
     * @return QueryFilterChain
     */
    public function insertFilter(FilterInterface $filter, $index)
    {
        if ($index >= count($this->filters) || $index < 0)
        {
            throw new \InvalidArgumentException(sprintf("Invalid index value '%d', filter chain has '%d' filters.", $index, count($this->filters)));
        }

        $filters = array();

        foreach ($this->filters as $old_index => $old_filter)
        {
            if ($old_index == $index)
            {
                $filters[] = $filter;
            }

            $filters[] = $old_filter;
        }

        $this->filters = $filters;

        return $this;
    }

    /**
     * replaceFilter
     *
     * Replace an existing filter with the given one.
     *
     * @param FilterInterface filter
     * @param Integer the index of the filter to be replaced.
     * @return QueryFilterChain
     */
    public function replaceFilter(FilterInterface $filter, $index)
    {
        if ($index < 0 or $index >= count($this->filters))
        {
            throw new \InvalidArgumentException(sprintf("No such index '%d' in filter chain.\nFilter chain definition is:\n%s\n", $index, print_r($this->dumpDefinition(), true)));
        }

        $this->filters[$index] = $filter;

        return $this;
    }
}
