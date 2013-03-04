<?php

namespace Pomm\FilterChain;

use Pomm\FilterChain\FilterInterface;
use Pomm\FilterChain\QueryFilterChain;
use Pomm\Tools\Logger;

/**
 * Pomm\FilterChain\LoggerFilter - The logger filter.
 *
 * @package Pomm
 * @uses Pomm\FilterChain\FilterInterface
 * @version $id$
 * @copyright 2011 Grégoire HUBERT
 * @author Grégoire HUBERT <hubert.greg@gmail.com>
 * @license X11 {@link http://opensource.org/licenses/mit-license.php}
 */
class LoggerFilter implements FilterInterface
{
    protected $logger;

    /**
     * __construct
     *
     * @param Pomm\Tools\Logger $logger
     **/
    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @see Pomm\FilterChain\FilterInterface
     **/
    public function execute(QueryFilterChain $query_filter_chain)
    {
        $time_start = microtime(true);
        $stmt = $query_filter_chain->executeNext($query_filter_chain);
        $time_end = microtime(true);

        $this->logger->add(array('sql' => $query_filter_chain->getSql(), 'params' => $query_filter_chain->getValues(), 'duration' => sprintf("%.1f ms", 1000 * ($time_end - $time_start)), 'results' => $stmt->rowCount(), 'time_start' => $time_start, 'map_class' => get_class($query_filter_chain->getMap())));

        return $stmt;
    }
}
