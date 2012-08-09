<?php
namespace Pomm\Tools;

use Pomm\Exception\ToolException;

class OutputLine 
{
    const LEVEL_DEBUG    = 1;
    const LEVEL_INFO     = 2;
    const LEVEL_WARNING  = 4;
    const LEVEL_ERROR    = 8;
    const LEVEL_CRITICAL = 16;
    const LEVEL_ALL      = 255;

    protected $level;
    protected $message;
    protected $timestamp;

    public function __construct($message, $level)
    {
        $this->message = $message;
        $this->level = $level;
        $this->timestamp = new \DateTime();
    }

    public function getLevel()
    {
        return $this->level;
    }

    public function __toString()
    {
        return sprintf("%s | %-10s | %s", $this->timestamp->format("Y-m-d H:i:s.u"), $this->getFormattedLevel(), $this->message);
    }

    public function getFormattedLevel()
    {
        switch($this->level)
        {
        case 1: return "DEBUG";
        case 2: return "INFO";
        case 4: return "WARNING";
        case 8: return "ERROR";
        case 16: return "CRITICAL";
        }
    }
}

