<?php
namespace Pomm\Connection;

use Pomm\Exception\Exception;
use Pomm\Tools\ParameterHolder;

/**
 * Database 
 * 
 * @package PommBundle
 * @version $id$
 * @copyright 2011 Grégoire HUBERT 
 * @author Grégoire HUBERT <hubert.greg@gmail.com>
 * @license MIT/X11 {@link http://opensource.org/licenses/mit-license.php}
 */
class Database 
{
  protected $parameter_holder = array();
  protected $_handler;

  /**
   * __construct 
   *
   * Parameters that can be sent :
   * dsn : an url like psql://user:pass@host:port/dbname
   * name : the connection name for this database (optionnal)
   * persistant : a boolean to use persistant connections or not (default true)
   * isolation : transaction isolation level (default READ COMMITED)
   *
   * @param array $parameters 
   * @access public
   * @return void
   */
  public function __construct($parameters = array())
  {
      $this->parameter_holder = new ParameterHolder($parameters);
      $this->initialize();
  }


  /**
   * processDsn 
   * Sets the different parameters from the DSN
   *
   * @access protected
   * @return void
   */
  protected function processDsn()
  {
    $dsn = $this->parameter_holder['dsn'];

    if (!preg_match('#([a-z]+)://(\w+)(?::([^@]+))?(?:@(\w+)(?::(\w+))?)?/(\w+)#', $dsn, $matchs))
    {
      throw new Exception(sprintf('Cound not parse DSN "%s".', $dsn));
    }


    if ($matchs[1] == null)
    {
      throw Exception(sprintf('No protocol information in dsn "%s".', $dsn));
    }
    $adapter = $matchs[1];

    if ($matchs[2] == null)
    {
      throw Exception(sprintf('No user information in dsn "%s".', $dsn));
    }
    $user = $matchs[2];
    $pass = $matchs[3];
    $host = $matchs[4];
    $port = $matchs[5];

    if ($matchs[6] == null)
    {
      throw Exception(sprintf('No database name in dsn "%s".', $dsn));
    }
    $database = $matchs[6];

    $this->parameter_holder->setParameter('adapter', $adapter);
    $this->parameter_holder->setParameter('user', $user);
    $this->parameter_holder->setParameter('pass', $pass);
    $this->parameter_holder->setParameter('host', $host);
    $this->parameter_holder->setParameter('port', $port);
    $this->parameter_holder->setParameter('database', $database);
  }


  /**
   * initialize
   *
   * This method initializes the parameters for our connection. It can be 
   * overloaded
   * @access protected
   * @return void
   * */
  protected function initialize()
  {
      $this->parameter_holder->mustHave('dsn');
      $this->processDsn();
      $this->parameter_holder->setDefaultValue('persistant', true);
  }

  /**
   * createConnection()
   *
   * Opens a new connection to the database
   * @access public
   * @return Connection
   **/
  public function createConnection()
  {
      return new TransactionConnection($this->parameter_holder);
  }
}
