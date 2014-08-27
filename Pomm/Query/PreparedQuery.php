<?php

namespace Pomm\Query;

use \Pomm\Connection\Connection;
use \Pomm\Exception\Exception as PommException;
use \Pomm\Exception\SqlException;
use \Pomm\Exception\ConnectionException;
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
    protected $connection;
    protected $sql;
    private $active = false;
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
        $this->sql = $sql;
        $this->name = static::getSignatureFor($sql);

        if (pg_send_prepare($this->connection->getHandler(), $this->name, $this->escapePlaceHolders($sql)) === false)
        {
            throw new ConnectionException(sprintf("Could not prepare statement «%s».", $sql));
        }

        $this->connection->getQueryResult();
        $this->active = true;
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
        if ($this->active === false)
        {
            throw new PommException(sprintf("Cannot execute inactive statement '%s'.", $this->getName()));
        }

        if (pg_send_execute($this->connection->getHandler(), $this->name, $this->prepareValues($values)) === false)
        {
            throw new ConnectionException(sprintf("Connection error while executing query '%s'.", $this->name));
        }

        return $this->connection->getQueryResult($this->sql);
    }

    /**
     * deallocate
     *
     * Deallocate the statement in the database.
     *
     * @access public
     * @return PreparedQuery
     */
    public function deallocate()
    {
        $res = @pg_execute($this->connection->getHandler(), sprintf("DEALLOCATE %s", $this->connection->escapeIdentifier($this->getName())));

        if ($res === false)
        {
            $this->connection->throwConnectionException(sprintf("Could not deallocate statement «%s».", $this->getName()), LogLevel::ERROR);
        }

        $this->active = false;

        return $this;
    }

    /**
     * getActive
     *
     * Return true if the statement is active, false othewise
     *
     * @return Bool
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * getSql
     *
     * Get the original SQL query
     *
     * @access public 
     * @return String SQL query
     */
    public function getSql()
    {
        return $this->sql;
    }

    /**
     * prepareValues
     *
     * Process the values for the query so they are understandable by Postgres.
     *
     * @access private
     * @param  Array    $values Query parameters
     * @return Array
     */
    private function prepareValues(array $values)
    {
        foreach ($values as $index => $value)
        {
            if ($value instanceof \DateTime)
            {
                $values[$index] = $value->format('Y-m-d H:i:s.uP');
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
