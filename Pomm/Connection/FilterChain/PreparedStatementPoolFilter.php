<?php

namespace Pomm\Connection\FilterChain;

use Pomm\Exception\Exception as PommException;
use Pomm\Query\PreparedQuery;

/**
 * Pomm\FilterChain\PreparedStatementPoolFilter 
 * This prepares and pools queries
 * 
 * @package Pomm
 * @uses Pomm\FilterChain\FilterInterface
 * @version $id$
 * @copyright 2011 Grégoire HUBERT 
 * @author Grégoire HUBERT <hubert.greg@gmail.com>
 * @license X11 {@link http://opensource.org/licenses/mit-license.php}
 */
class PreparedStatementPoolFilter implements FilterInterface
{
    protected $filte_chain;
    protected $queries = array();

    public function execute(QueryFilterChain $filter_chain)
    {
        $this->filter_chain = $filter_chain;
        $filter_chain->query = $this->getQuery($filter_chain->query);

        return $filter_chain->executeNext();
    }

    /**
     * getquery
     *
     * Return a prepared query from a sql signature. If no query match the
     * signature in the pool, a new query is prepared.
     *
     * @access protected
     * @return PreparedQuery
     */
    protected function getQuery($sql)
    {
        $signature = PreparedQuery::getSignatureFor($sql);

        if ($this->hasQuery($signature) === false)
        {
            $query = $this->createPreparedQuery($sql);
            $this->queries[$query->getName()] = $query;
        }

        return $this->queries[$signature];
    }

    /**
     * hasQuery
     *
     * Check if a query with the given signature exists in the pool.
     *
     * @access protected
     * @param  String $name Query signature.
     * @return Boolean
     */
    protected function hasQuery($name)
    {
        return (bool) isset($this->queries[$name]);
    }

    /**
     * createPreparedQuery
     *
     * @access protected
     * @param String $sql Statement to prepare.
     * @return PreparedQuery
     */
    protected function createPreparedQuery($sql)
    {
        return new PreparedQuery($this->filter_chain->getConnection(), $sql);
    }

}

