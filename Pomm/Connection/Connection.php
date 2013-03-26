<?php

namespace Pomm\Connection;

use Pomm\Exception\Exception;
use Pomm\Connection\Database;
use Pomm\FilterChain\QueryFilterChain;
use Pomm\Object\BaseObjectMap;
use Pomm\FilterChain\PDOQueryFilter;
use Pomm\FilterChain\FilterInterface;

/**
 * Pomm\Connection\Connection
 * Manage a connection and related transactions
 * 
 * @package Pomm
 * @version $id$
 * @copyright 2011 Grégoire HUBERT 
 * @author Grégoire HUBERT <hubert.greg@gmail.com>
 * @license X11 {@link http://opensource.org/licenses/mit-license.php}
 */
class Connection
{
    const ISOLATION_READ_COMMITTED = "READ COMMITTED";
    const ISOLATION_READ_REPEATABLE = "READ REPEATABLE"; // from Pg 9.1
    const ISOLATION_SERIALIZABLE = "SERIALIZABLE";

    protected $handler;
    protected $database;
    protected $parameter_holder;
    protected $isolation;
    protected $in_transaction = false;
    protected $identity_mapper;
    protected $query_filter_chain;
    protected $maps = array();

    /**
     * __construct()
     * 
     * Connection instance to the specified database.
     *
     * @access public
     * @param Database                  $database   The Database instance.
     * @param IdentityMapperInterface   $mapper     The optional identity mapper instance.
     **/
    public function __construct(Database $database, \Pomm\Identity\IdentityMapperInterface $mapper = null)
    {
        $this->database = $database;
        $this->query_filter_chain = new QueryFilterChain($this);
        $this->query_filter_chain->registerFilter(new PDOQueryFilter());

        $this->parameter_holder = $database->getParameterHolder();

        $this->parameter_holder->setDefaultValue('isolation', self::ISOLATION_READ_COMMITTED);
        $this->parameter_holder->mustBeOneOf('isolation', 
            array(self::ISOLATION_READ_COMMITTED, self::ISOLATION_SERIALIZABLE, self::ISOLATION_READ_REPEATABLE)
        );

        $this->isolation = $this->parameter_holder['isolation'];
        $this->parameter_holder->setDefaultValue('identity_mapper', false);

        if (is_null($mapper))
        {
            if ($this->parameter_holder['identity_mapper'] !== false)
            {
                $identity_class = $this->parameter_holder['identity_mapper'] === true ? 'Pomm\Identity\IdentityMapperSmart' : $this->parameter_holder['identity_mapper'];

                $this->identity_mapper = new $identity_class();
            }
        }
        else
        {
            $this->identity_mapper = $mapper;
        }
    }

    /**
     * launch
     *
     * Open a connection on the database.
     * @access protected
     **/
    protected function launch()
    {
        $connect_string = sprintf('%s:dbname=%s', 
            $this->parameter_holder['adapter'],
            $this->parameter_holder['database']
        );

        $connect_string .= $this->parameter_holder['host'] !== '' ? sprintf(';host=%s', $this->parameter_holder['host']) : '';
        $connect_string .= $this->parameter_holder['port'] !== '' ? sprintf(';port=%d', $this->parameter_holder['port']) : '';

        try
        {
            $this->handler = new \PDO($connect_string, $this->parameter_holder['user'], $this->parameter_holder['pass'] != '' ? $this->parameter_holder['pass'] : null);
        }
        catch (\PDOException $e)
        {
            throw new Exception(sprintf('Error connecting to the database with dsn «%s». Driver said "%s".', $connect_string, $e->getMessage()));
        }

        // required for pg8.4 -> pg9.1 compatibility
        $this->handler->exec('SET standard_conforming_strings TO off');
    }

    /*
     * __destruct
     *
     * @access public
     * @return void
     */
    public function __destruct()
    {
        unset($this->handler);
    }

    /**
     * getPdo 
     *
     * Returns the PDO instance of the associated connection.
     * 
     * @access public
     * @return PDO
     */
    public function getPdo()
    {
        if (!isset($this->handler))
        {
            $this->launch();
        }

        return $this->handler;
    }

    /**
     * getMapFor 
     *
     * Returns a Map instance of the given model name.
     * 
     * @param  String $class The fully qualified class name of the associated entity.
     * @param  Bool   $force Force the creation of a new Map instance.
     * @access public
     * @return \Pomm\Object\BaseObjectMap
     */
    public function getMapFor($class, $force = false)
    {
        $class = trim($class, '\\');
        $class_name = $class.'Map';

        if ($force === true or !array_key_exists($class, $this->maps))
        {
            $this->maps[$class] = new $class_name($this);
        }

        return $this->maps[$class];
    }

    /**
     * getDatabase
     *
     * Returns the connection's database.
     *
     * @access public
     * @return Database
     **/
    public function getDatabase()
    {
        return $this->database;
    }

    /**
     * begin
     *
     * Start a new transaction.
     *
     * @return Pomm\Connection\Connection
     **/
    public function begin()
    {
        if ($this->in_transaction)
        {
            throw new Exception("Cannot begin a new transaction, we are already in a transaction.");
        }

        $this->in_transaction = 0 === $this->getPdo()->exec(sprintf("BEGIN TRANSACTION ISOLATION LEVEL %s", $this->isolation));

        return $this;
    }

    /**
     * commit
     *
     * Commit a transaction in the database.
     *
     * @return Pomm\Connection\Connection
     **/
    public function commit()
    {
        if (! $this->in_transaction)
        {
            throw new Exception("COMMIT while not in a transaction");
        }

        $this->in_transaction = 0 !== $this->getPdo()->exec('COMMIT');

        return $this;
    }

    /**
     * rollback
     * 
     * rollback a transaction. This can be the whole transaction
     * or if a savepoint name is specified only the queries since
     * this savepoint.
     *
     * @param  String $name Optionnal name of the savepoint.
     * @return Pomm\Connection\Connection
     **/
    public function rollback($name = null)
    {
        if (! $this->in_transaction)
        {
            throw new Exception("ROLLBACK while not in a transaction");
        }

        if (is_null($name))
        {
            $this->getPdo()->exec('ROLLBACK TRANSACTION');
            $this->in_transaction = false;
        }
        else
        {
            $this->getPdo()->exec(sprintf("ROLLBACK TO SAVEPOINT %s", $name));
        }

        return $this;
    }


    /**
     * setSavepoint
     *
     * Set a new savepoint with the given name.
     *
     * @param String $name Savepoint's name.
     * @return Pomm\Connection\Connection
     **/
    public function setSavepoint($name)
    {
        $this->getPdo()->exec(sprintf("SAVEPOINT %s", $name));

        return $this;
    }

    /**
     * releaseSavepoint
     *
     * Forget the specified savepoint.
     *
     * @param String $name the savepoint's name
     * @return Pomm\Connection\Connection
     **/
    public function releaseSavepoint($name)
    {
        $this->getPdo()->exec(sprintf("RELEASE SAVEPOINT %s", $name));

        return $this;
    }

    /**
     * isInTransaction
     *
     * Check if we are in transaction mode.
     *
     * @return boolean
     **/
    public function isInTransaction()
    {
        return (bool) $this->in_transaction;
    }

    /**
     * getIdentityMapper
     *
     * Get connection's related identity mapper.
     *
     * @return IdentityMapperInterface
     **/
    public function getIdentityMapper()
    {
        return $this->identity_mapper;
    }

    /**
     * registerFilter
     *
     * Register a new Filter in the QueryFilterChain.
     *
     * @param \Pomm\FilterChain\FilterInterface
     * @return \Pomm\Connection\Connection
     **/
    public function registerFilter(FilterInterface $filter)
    {
        $this->query_filter_chain->registerFilter($filter);

        return $this;
    }

    /**
     * executeFilterChain
     *
     * Execute a SQL Query in the filter chain.
     *
     * @param BaseObjectMap $map     Map instance that sends the query.
     * @param String        $sql     The SQL query.
     * @param Array         $values  Optional parameter for the prepared query.
     * @return PDOStatement
     **/
    public function executeFilterChain(BaseObjectMap $map, $sql, Array $values = array())
    {
        return $this->query_filter_chain->execute($map, $sql, $values);
    }

    /**
     * executeAnonymousQuery
     * Performs a raw SQL query
     *
     * @param String $sql The sql statement to execute.
     * @return \PDOStatement
     */
    public function executeAnonymousQuery($sql)
    {
        return $this->getPdo()->query($sql, \PDO::FETCH_LAZY);
    }
}
