<?php

namespace Pomm\Connection;

use Pomm\Exception\Exception;
use Pomm\Connection\Database;

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
    const ISOLATION_SERIALIZABLE = "SERIALIZABLE";

    protected $handler;
    protected $database;
    protected $parameter_holder;

    /**
     * __construct()
     * 
     * open a connection to the specified database
     * @access public
     * @param ParameterHolder $parameter_holder the db parameters
     **/
    public function __construct(Database $database)
    {
        $this->database = $database;

        $this->parameter_holder = $database->getParameterHolder();

        $this->parameter_holder->setDefaultValue('isolation', self::ISOLATION_READ_COMMITTED);
        $this->parameter_holder->mustBeOneOf('isolation', 
            array(self::ISOLATION_READ_COMMITTED, self::ISOLATION_SERIALIZABLE)
        );
    }

    protected function launch()
    {
        $connect_string = sprintf('%s:dbname=%s user=%s', 
            $this->parameter_holder['adapter'],
            $this->parameter_holder['database'],
            $this->parameter_holder['user'] 
        );

        $connect_string .= $this->parameter_holder['host'] !== '' ? sprintf(' host=%s', $this->parameter_holder['host']) : '';
        $connect_string .= $this->parameter_holder['port'] !== '' ? sprintf(' port=%d', $this->parameter_holder['port']) : '';
        $connect_string .= $this->parameter_holder['pass'] !== '' ? sprintf(' password=%s', $this->parameter_holder['pass']) : '';

        try
        {
            $this->handler = new \PDO($connect_string);
        }
        catch (\PDOException $e)
        {
            throw new Exception(sprintf('Error connecting to the database with dsn «%s». Driver said "%s".', $connect_string, $e->getMessage()));
        }
    }

    /*
     * __destruct
     *
     * The destructor
     * @access public
     * @return void
     */
    public function __destruct()
    {
        unset($this->handler);
    }

    /**
     * getPdo 
     * Returns the PDO instance of the associated connection
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
     * Returns a Map instance of the given model name
     * 
     * @param string $class 
     * @access public
     * @return PommBaseObjectMap
     */
    public function getMapFor($class)
    {
        $class_name = $class.'Map';
        $object = new $class_name($this);

        return $object;
    }

  /**
   * getDatabase
   * Returns the connection's database
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
   * Starts a new transaction
   *
   * @return Pomm\Connection\Connection
   **/
  public function begin()
  {
      $this->getPdo()->exec(sprintf("BEGIN TRANSACTION ISOLATION LEVEL %s", $this->parameter_holder['isolation']));

      return $this;
  }

  /**
   * commit
   * Commits a transaction in the database
   *
   * @return Pomm\Connection\Connection
   **/
  public function commit()
  {
      $this->getPdo()->exec('COMMIT');

      return $this;
  }

  /**
   * rollback
   * rollback a transaction. This can be the whole transaction
   * or if a savepoint name is specified only the queries since
   * this savepoint.
   *
   * @param String $name the name of the savepoint (optionnal)
   * @return Pomm\Connection\Connection
   **/
  public function rollback($name = null)
  {
      if (is_null($name))
      {
          $this->getPdo()->rollback();
      }
      else
      {
          $this->getPdo()->exec(sprintf("ROLLBACK TO SAVEPOINT %s", $name));
      }

      return $this;
  }


  /**
   * setSavepoint
   * Set a new savepoint with the given name
   *
   * @param String $name the savepoint's name
   * @return Pomm\Connection\Connection
   **/
  public function setSavepoint($name)
  {
      $this->getPdo()->exec(sprintf("SAVEPOINT %s", $name));

      return $this;
  }

  /**
   * releaseSavepoint
   * forget the specified savepoint
   *
   * @param String $name the savepoint's name
   * @return Pomm\Connection\Connection
   **/
  public function releaseSavepoint($name)
  {
      $this->getPdo()->exec(sprintf("RELEASE SAVEPOINT %s", $name));

      return $this;
  }
}
