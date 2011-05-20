<?php

namespace Pomm\Connection;

use Pomm\Tools\ParameterHolder;
use Pomm\Exception\Exception;
use Pomm\Connection\Database;

/**
 * Connection
 * 
 * @package PommBundle
 * @version $id$
 * @copyright 2011 Grégoire HUBERT 
 * @author Grégoire HUBERT <hubert.greg@gmail.com>
 * @license MIT/X11 {@link http://opensource.org/licenses/mit-license.php}
 */
class Transaction extends Connection
{
    const ISOLATION_READ_COMMITTED = "READ COMMITTED";
    const ISOLATION_SERIALIZABLE = "SERIALIZABLE";

    public function __construct(Database $database)
    {
        parent::__construct($database);

        $this->parameter_holder->setDefaultValue('isolation', self::ISOLATION_READ_COMMITTED);
        $this->parameter_holder->mustBeOneOf('isolation', 
            array(self::ISOLATION_READ_COMMITTED, self::ISOLATION_SERIALIZABLE)
        );
    }

  /**
   * begin()
   *
   * Starts a new transaction
   * @access public
   **/
  public function begin()
  {
      $this->getPdo()->exec(sprintf("BEGIN TRANSACTION ISOLATION LEVEL %s", $this->parameter_holder['isolation']));

      return $this;
  }

  /**
   * commit()
   *
   * Commits a transaction in the database
   * @access public
   **/
  public function commit()
  {
      $this->getPdo()->exec('COMMIT');

      return $this;
  }

  /**
   * rollback()
   *
   * rollback a transaction. This can be the whole transaction
   * or if a savepoint name is specified only the queries since
   * this savepoint.
   * @access public
   * @param String $name the name of the savepoint (opionnal)
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
   * setSavepoint()
   *
   * Set a new savepoint with the given name
   * @access public
   * @param String $name the savepoint's name
   **/
  public function setSavepoint($name)
  {
      $this->getPdo()->exec(sprintf("SAVEPOINT %s", $name));

      return $this;
  }

  /**
   * releaseSavepoint()
   *
   * forget the specified savepoint
   * @access public
   * @param String $name the savepoint's name
   **/
  public function releaseSavepoint($name)
  {
      $this->getPdo()->exec(sprintf("RELEASE SAVEPOINT %s", $name));

      return $this;
  }
}
