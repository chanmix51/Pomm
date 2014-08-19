<?php

namespace Pomm\Connection\FilterChain;

use Pomm\Exception\Exception as PommException;
use Pomm\Query\PreparedQuery;

/**
 * Pomm\FilterChain\QueryFilter - The query filter.
 * 
 * @package Pomm
 * @uses Pomm\FilterChain\FilterInterface
 * @version $id$
 * @copyright 2011 Grégoire HUBERT 
 * @author Grégoire HUBERT <hubert.greg@gmail.com>
 * @license X11 {@link http://opensource.org/licenses/mit-license.php}
 */
class QueryFilter implements FilterInterface
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
     * doQuery 
     *
     * Performs a query, returns the result resource instance.
     * 
     * @access protected
     * @return resource result
     */
    protected function doQuery()
    {
        if (!$this->filter_chain->query instanceOf PreparedQuery)
        {
            throw new PommException(sprintf("QueryFilter expecting the query to be a PreparedQuery instance, '%s' given.", is_object($query) ? get_class($query) : gettype($query)));
        }

        return $this->filter_chain->query->execute($this->filter_chain->values);
    }
}
