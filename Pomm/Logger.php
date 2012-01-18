<?php

namespace Pomm;

class Logger
{
    public $queries = array();

    public $start = null;

    public $currentQuery = 0;

    public function startQuery($sql, array $params = null)
    {
        $this->start = microtime(true);
        $this->queries[++$this->currentQuery] = array('sql' => $sql, 'params' => $params, 'time' => 0);
    }

    public function stopQuery()
    {
        $this->queries[$this->currentQuery]['time'] = microtime(true) - $this->start;
    }
}
