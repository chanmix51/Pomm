<?php

namespace Pomm\Connection\FilterChain;

use Psr\Log\LogLevel;

class LoggerFilter implements FilterInterface
{
    /**
     * {@inheritdoc}
     */
    public function execute(QueryFilterChain $query_filter_chain)
    {
        $query_filter_chain->getConnection()->log(LogLevel::DEBUG, sprintf("Sql query ===\n%s\n===\n, parameters = %s.", $query_filter_chain->query, print_r($query_filter_chain->values, true)));
        $start = microtime(true);
        $stmt = $query_filter_chain->executeNext();
        $end = microtime(true);
        $query_filter_chain->getConnection()->log(LogLevel::DEBUG, sprintf("(%d results) %s.", pg_num_rows($stmt), $this->formatTimeDiff($start, $end)));

        return $stmt;
    }

    protected function formatTimeDiff($start, $end)
    {
        return sprintf("%03.1f ms", ($end - $start) * 1000);
    }
}
