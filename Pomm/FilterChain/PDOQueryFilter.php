<?php

namespace Pomm\FilterChain;

use Pomm\Exception\Exception;
use Pomm\Exception\SqlException;
use Pomm\FilterChain\FilterInterface;
use Pomm\FilterChain\QueryFilterChain;

/**
 * Pomm\FilterChain\PDOQueryFilter - The query filter.
 * 
 * @package Pomm
 * @uses Pomm\FilterChain\FilterInterface
 * @version $id$
 * @copyright 2011 Grégoire HUBERT 
 * @author Grégoire HUBERT <hubert.greg@gmail.com>
 * @license X11 {@link http://opensource.org/licenses/mit-license.php}
 */
class PDOQueryFilter implements FilterInterface
{

    protected $filter_chain;

    /**
     * {@inheritdoc}
     */
    public function execute(QueryFilterChain $query_filter_chain)
    {
        $this->filter_chain = $query_filter_chain;

        return $this->doQuery();
    }

    /**
     * prepareStatement 
     *
     * Prepare a SQL statement.
     * 
     * @access protected
     * @return \PDOStatement
     */
    protected function prepareStatement()
    {
        return $this->filter_chain->getConnection()->getPdo()->prepare($this->filter_chain->getSql());
    }

    /**
     * bindParams 
     *
     * Bind parameters to a prepared statement.
     * 
     * @param \PDOStatement $stmt
     * @access protected
     * @return \PDOStatement
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
                if ($value instanceof \DateTime)
                {
                    $value = $value->format('Y-m-d H:i:s.u');
                }

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
     *
     * Performs a query, returns the PDO Statement instance used.
     * 
     * @access protected
     * @return \PDOStatement
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
            throw new Exception(sprintf("PDOException while performing SQL query «%s». The driver said '%s'.", $sql, $e->getMessage()));
        }

        return $stmt;
    }
}
