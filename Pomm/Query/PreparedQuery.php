<?php
namespace Pomm\Query;

use \Pomm\Exception\ConnectionException;

class PreparedQuery
{
    private $handler;
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
     * @param Resource $handler Database handler
     * @param String   $sql     SQL query
     */
    public function __construct($handler, $sql)
    {
        $this->handler = $handler;
        $this->name = static::getSignatureFor($sql);
        $this->stmt = pg_prepare($this->handler, $this->name, $this->escapePlaceHolders($sql));

        if ($this->stmt === false)
        {
            throw new ConnectionException(sprintf("Could not prepare statement «%s».", $sql));
        }
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
        @pg_query($this->handler, sprintf("DEALLOCATE \"%s\"", $this->getName()));
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
    public function execute($values)
    {
        $res = pg_execute($this->handler, $this->name, $this->prepareValues($values));

        if ($res === false)
        {
            throw new ConnectionException(sprintf("Error while executing prepared statement '%s'.", $this->getName()));
        }

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

    private function escapePlaceHolders($sql)
    {
        return preg_replace_callback('/ \$\*/', function ($sub) { static $nb = 0; return sprintf(" $%d", ++$nb); }, $sql );
    }

}
