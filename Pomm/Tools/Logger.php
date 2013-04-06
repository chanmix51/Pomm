<?php

namespace Pomm\Tools;

/**
 * Logger
 *
 * @package Pomm
 * @version $id$
 * @copyright 2011 Grégoire HUBERT
 * @author Grégoire HUBERT <hubert.greg@gmail.com>
 * @license X11 {@link http://opensource.org/licenses/mit-license.php}
 */
class Logger
{
    protected $logs = array();

    /**
     * add
     *
     * Add a new log line to the logger.
     *
     * @param String $status
     */
    public function add($status)
    {
        $this->logs[] = $status;
    }

    /**
     * getLogs
     *
     * Get all stored log lines.
     *
     * @return Array
     */
    public function getLogs()
    {
        return $this->logs;
    }
}
