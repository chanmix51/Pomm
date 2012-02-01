<?php

namespace Pomm\Tools;

class Logger
{
    protected $logs = array();

    public function add($status)
    {
        $this->logs[] = $status;
    }

    public function getLogs()
    {
        return $this->logs;
    }
}
