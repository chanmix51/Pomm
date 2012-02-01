<?php

namespace Pomm\FilterChain;

use Pomm\Exception\Exception;
use Pomm\FilterChain\FilterInterface;
use Pomm\FilterChain\QueryFilterChain;
use Pomm\Tools\Logger;

class LoggerFilter implements FilterInterface
{
    protected $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    public function execute(QueryFilterChain $query_filter_chain)
    {
        $time_start = microtime(true);
        $stmt = $query_filter_chain->executeNext($query_filter_chain);
        $time_end = microtime(true);

        $this->logger->add(array('sql' => $query_filter_chain->getSql(), 'params' => $query_filter_chain->getValues(), 'duration' => $time_end - $time_start, 'results' => $stmt->rowCount(), 'time_start' => $time_start));

        return $stmt;
    }
}
