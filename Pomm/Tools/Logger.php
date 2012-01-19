<?php

namespace Pomm\Tools;

class Logger
{
    protected $logs = array();

    public function add($sql, Array $values = array(), $time = 0)
    {
        $this->logs[] = array('sql' => $sql, 'params' => $values, 'time' => $time);
    }

    public function getLogs()
    {
        return $this->logs;
    }
}
