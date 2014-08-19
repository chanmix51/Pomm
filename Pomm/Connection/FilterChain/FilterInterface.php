<?php

namespace Pomm\Connection\FilterChain;

/**
 * Pomm\Connection\FilterChain\FilterInterface - Interface for filters.
 * 
 * @package Pomm
 * @uses Pomm\Exception\Exception
 * @version $id$
 * @copyright 2011 Grégoire HUBERT 
 * @author Grégoire HUBERT <hubert.greg@gmail.com>
 * @license X11 {@link http://opensource.org/licenses/mit-license.php}
 */
interface FilterInterface
{
    /**
     * execute
     *
     * Run the filter.
     *
     * @param QueryFilterChain $query_filter_chain
     * @return resource result
     */
    public function execute(QueryFilterChain $query_filter_chain);
}
