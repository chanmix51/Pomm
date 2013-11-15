<?php

namespace Pomm\Query;

use \Pomm\Connection\Connection;
use \Psr\Log\LogLevel;

/**
 * Pomm\Query\PreparedQuery
 *
 * @package Pomm
 * @version $id$
 * @copyright 2013 Grégoire HUBERT
 * @author Grégoire HUBERT <hubert.greg@gmail.com>
 * @license X11 {@link http://opensource.org/licenses/mit-license.php}
 */
class PreparedQuery
{
    private $connection;
    private $stmt;
    private $name;

    /**
     * getSignatureFor
     *
     * Returns a hash for a given sql query.
     *
     * @static
     * @access public
     * @param String $sql Sql query
     * @return String
     */
    static public function getSignatureFor($sql)
    {
        return md5($sql);
    }

    /**
     * __construct
     *
     * Build the prepared query.
     *
     * @access public
     * @param Connection $connection
     * @param String   $sql     SQL query
     */
    public function __construct(Connection $connection, $sql)
    {
        $this->connection = $connection;
        $this->name = static::getSignatureFor($sql);
        $this->stmt = pg_prepare($this->connection->getHandler(), $this->name, $this->escapePlaceHolders($sql));

        if ($this->stmt === false)
        {
            $this->connection->throwConnectionException(sprintf("Could not prepare statement «%s».", $sql), LogLevel::ERROR);
        }

        $this->connection->log(LogLevel::INFO, sprintf("Prepared query '%s' => -- %s --", $this->name, $sql));
    }

    /**
     * __destruct
     *
     * Deallocate the prepared query.
     *
     * @access public
     */
    public function __destruct()
    {
        $this->connection->log(LogLevel::INFO, sprintf("Freeing query '%s'.", $this->getName()));
        @pg_query($this->connection->getHandler(), sprintf("DEALLOCATE \"%s\"", $this->getName()));
    }

    /**
     * getName
     *
     * Return the query name.
     *
     * @access public
     * @return String Query name.
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * execute
     *
     * Launch the query with the given parameters.
     *
     * @access public
     * @param  Array $values Query parameters
     * @return Resource
     */
    public function execute(Array $values = array())
    {
        $res = @pg_execute($this->connection->getHandler(), $this->name, $this->prepareValues($values));

        if ($res === false)
        {
            $this->connection->throwConnectionException(sprintf("Error while executing prepared statement '%s'.", $this->getName()), LogLevel::ERROR);
        }

        $this->connection->log(LogLevel::DEBUG, sprintf("Execute '%s' (%d results) with values (%s).", $this->name, @pg_num_rows($res), print_r($values, true)));

        return $res;
    }

    /**
     * prepareValues
     *
     * Process the values for the query so they are understandable by Postgres.
     * There is a big #TODO here as Postgresql date format can be set in the server configuration.
     *
     * @access private
     * @param  Array    $values Query parameters
     * @return Array
     */
    private function prepareValues($values)
    {
        foreach (new \ArrayIterator($values) as $index => $value)
        {
            if ($value instanceof \DateTime)
            {
                $values[$index] = $value->format('Y-m-d H:i:s.u');
            }
        }

        return $values;
    }

    /**
     * escapePlaceHolders
     *
     * Replace Pomm values placeholders by indexed placeholders.
     *
     * @access private
     * @param String $sql SQL statement
     * @return String PHP Pg compatible sql statement.
     */
    private function escapePlaceHolders($sql)
    {
        return preg_replace_callback('/\$\*/', function () { static $nb = 0; return sprintf("$%d", ++$nb); }, $sql );
    }
}
