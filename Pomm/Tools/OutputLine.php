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

    /**
     * __construct
     *
     */
    public function __construct($message, $level)
    {
        $this->message = $message;
        $this->level = $level;
        $this->timestamp = new \DateTime();
    }

    /**
     * getLevel
     *
     * @return Integer Current line's severity level.
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * __toString
     *
     * @return String
     */
    public function __toString()
    {
        return sprintf("%s | %-10s | %s", $this->timestamp->format("Y-m-d H:i:s.u"), $this->getFormattedLevel(), $this->message);
    }

    /**
     * getFormattedLevel
     *
     * @return String
     */
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
