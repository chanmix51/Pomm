<?php

namespace Pomm\FilterChain;

use Pomm\Exception\Exception;
use Pomm\FilterChain\QueryFilterChain;

/**
 * Pomm\FilterChain\FilterInterface - Interface for filters.
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
     * @param Pomm\FilterChain\QueryFilterChain $query_filter_chain
     * @return \PDOStatement
     **/
    public function execute(QueryFilterChain $query_filter_chain);
}
