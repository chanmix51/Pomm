<?php

namespace Pomm\FilterChain;

use Pomm\Exception\Exception;
use Pomm\FilterChain\FilterInterface;
use Pomm\FilterChain\QueryFilterChain;

class PDOQueryFilter implements FilterInterface
{

    protected $filter_chain;

    public function execute(QueryFilterChain $query_filter_chain)
    {
        $this->filter_chain = $query_filter_chain;

        return $this->doQuery();
    }

    /**
     * prepareStatement 
     * Prepare a SQL statement
     * 
     * @access protected
     * @return PDOStatement
     */
    protected function prepareStatement()
    {
        return $this->filter_chain->getConnection()->getPdo()->prepare($this->filter_chain->getSql(), array(\PDO::CURSOR_SCROLL));
    }

    /**
     * bindParams 
     * Bind parameters to a prepared statement
     * 
     * @param PDOStatement $stmt 
     * @access protected
     * @return PDOStatement
     */
    protected function bindParams($stmt)
    {
        foreach ($this->filter_chain->getValues() as $pos => $value)
        {
            if (is_integer($value))
            {
                $type = \PDO::PARAM_INT;
            }
            elseif (is_bool($value))
            {
                $type = \PDO::PARAM_BOOL;
            }
            else
            {
                $type = null;
            }

            if (is_null($type))
            {
                $stmt->bindValue($pos + 1, $value);
            }
            else
            {
                $stmt->bindValue($pos + 1, $value, $type);
            }
        }

        return $stmt;
    }

    /**
     * doQuery 
     * Performs a query, returns the PDO Statment instance used
     * 
     * @access protected
     * @return PDOStatement
     */
    protected function doQuery()
    {
        $sql = $this->filter_chain->getSql();

        $stmt = $this->bindParams($this->prepareStatement());
        try
        {
            if (!$stmt->execute())
            {
                throw new SqlException($stmt, $sql);
            }
        }
        catch(\PDOException $e)
        {
            throw new Exception('PDOException while performing SQL query «%s». The driver said "%s".', $sql, $e->getMessage());
        }

        return $stmt;
    }
}
