<?php
namespace Pomm\Connection;

use Pomm\Exception\Exception as PommException;

/**
 * Observer
 *
 * This class can listen to NOTIFY events sent by another process trough the 
 * server.
 *
 * @package Pomm
 * @version $id$
 * @copyright 2011 - 2013 Grégoire HUBERT
 * @author Grégoire HUBERT <hubert.greg@gmail.com>
 * @license X11 {@link http://opensource.org/licenses/mit-license.php}
 **/
class Observer
{
    protected $name;
    protected $connection;

    /**
     * __construct
     *
     * @param Connection
     * @param String name the name of the event to listen to.
     */
    public function __construct(\Pomm\Connection\Connection $connection)
    {
        $this->connection = $connection;
    }


    /**
     * __destruct
     *
     * Unlisten if listening.
     */
    public function __destruct()
    {
        if ($this->isListening())
        {
            $this->unlisten();
        }
    }

    /**
     * listen
     *
     * Start to listen.
     *
     * @return Observer
     */
    public function listen($name)
    {
        if (empty($name))
        {
            throw new PommException(sprintf("Event name cannot be empty."));
        }

        $name = \pg_escape_identifier($this->connection->getHandler(), $name);
        $this->connection->executeAnonymousQuery(sprintf("LISTEN %s", $name));
        $this->name = $name;

        return $this;
    }

    /**
     * unlisten
     *
     * Stop listening.
     *
     * @return Observer
     */
    public function unlisten()
    {
        $this->connection->executeAnonymousQuery(sprintf("UNLISTEN %s", $this->name));
        $this->name = null;

        return $this;
    }

    /**
     * isListening
     *
     * Return true or false whereas the observer is listening
     *
     * @return Boolean
     */
    public function isListening()
    {
        return (bool) $this->name !== null;
    }

    /**
     * getNotification
     *
     * If a notification has been sent, return it or false otherwise.
     *
     * @throw \Pomm\Exception\Exception if the observer is not listening.
     * @return mixed
     */
    public function getNotification()
    {
        if ($this->isListening() === false)
        {
            throw new PommException(sprintf("The observer is not listening."));
        }

        return \pg_get_notify($this->connection->getHandler(), \PGSQL_ASSOC);
    }

    /**
     * getName
     *
     * Returns the current observer's name
     *
     * @return String or null when not listening.
     */
    public function getName()
    {
        return $this->name;
    }
}
