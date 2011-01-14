<?php
namespace Pomm\Connection;
use \Pomm\Exception;

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
  protected $parameters = array();
  protected $_handler;

  /**
   * __construct 
   *
   * Parameters that can be sent :
   * dsn : an url like psql://user:pass@host:port/dbname
   * name : the connection name for this database (optionnal)
   * persistant : a boolean to use persistant connections or not (default true)
   *
   * @param array $parameters 
   * @access public
   * @return void
   */
  public function __construct($parameters = array())
  {
    $this->initialize($parameters);
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
      $this->shutdown();
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
    $dsn = $this->getParameter('dsn');

    if (!preg_match('#([a-z]+):(?://(\w+)(?::(\w+))?@(\w+)(?::(\w+))?)?/(\w+)#', $dsn, $matchs))
    {
      throw new \Pomm\Exception\Exception(sprintf('Cound not parse DSN "%s".', $dsn));
    }


    if ($matchs[1] == null)
    {
      throw Pomm\Exception\Exception(sprintf('No protocol information in dsn "%s".', $dsn));
    }
    $adapter = $matchs[1];

    if ($matchs[2] == null)
    {
      throw Pomm\Exception\Exception(sprintf('No user information in dsn "%s".', $dsn));
    }
    $user = $matchs[2];
    $pass = $matchs[3];

    if ($matchs[4] == null)
    {
      throw Pomm\Exception\Exception(sprintf('No hostname name in dsn "%s".', $dsn));
    }
    $host = $matchs[4];
    $port = $matchs[5];

    if ($matchs[6] == null)
    {
      throw Pomm\Exception\Exception(sprintf('No database name in dsn "%s".', $dsn));
    }
    $database = $matchs[6];

    $this->setParameter('adapter', $adapter);
    $this->setParameter('user', $user);
    $this->setParameter('pass', $pass);
    $this->setParameter('host', $host);
    $this->setParameter('port', $port);
    $this->setParameter('database', $database);
  }

  /**
   * connect 
   *
   * @access public
   * @return void
   */
  public function connect()
  {
    $connect_string = sprintf('%s:host=%s dbname=%s user=%s', 
        $this->getParameter('adapter'),
        $this->getParameter('host'),
        $this->getParameter('database'),
        $this->getParameter('user') 
        );

    $connect_string .= $this->getParameter('port') !== '' ? sprintf(' port=%d', $this->getParameter('port')) : '';
    $connect_string .= $this->getParameter('pass') !== '' ? sprintf(' password=%s', $this->getParameter('pass')) : '';

    try
    {
      $this->_handler = new \PDO($connect_string);
    }
    catch (\PDOException $e)
    {
      throw new Pomm\Exception\Exception(sprintf('Error connecting to the database with dsn «%s». Driver said "%s".', $connect_string, $e->getMessage()));
    }
  }

  /**
   * shutdown 
   * 
   * @access public
   * @return void
   */
  public function shutdown()
  {
    $this->_handler = null;
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
    if (is_null($this->_handler))
    {
      $this->connect();
    }

    return $this->_handler;
  }

  /*
   * setParameter
   *
   * Set a parameter
   * @access public
   * @param string name the parameter's name
   * @param string value the parameter's value
   * @return void
   * */
  public function setParameter($name, $value) 
  {
      $this->parameters[$name] = $value;
  }

  /*
   * hasParameter
   *
   * check if parameter exists
   * @access public
   * @param string name the parameter's name
   * @return boolean
   * */
  public function hasParameter($name)
  {
      return array_key_exists($name, $this->parameters);
  }

  /*
   * getParameter
   *
   * Returns the parameter "name" or "default" if not set
   * @access public
   * @param string name the parameter's name
   * @param string default optionnal default value if name not set
   * @return string the parameter's value or default
   * */
  public function getParameter($name, $default = null)
  {
      return $this->hasParameter($name) ? $this->parameters[$name] : $default;
  }

  /**
   * initialize
   *
   * This method initializes the parameters for our connection. It can be 
   * overloaded
   * @access protected
   * @param array parameters the parameters passed to the contructor
   * @return void
   * */
  protected function initialize($parameters = array())
  {
      $this->parameters = $parameters;

      if (!$this->hasParameter('dsn'))
      {
          throw new PommException('No dsn given');
      }
      $this->processDsn();

      if (!$this->hasParameter('persistant'))
      {
          $this->setParameter('persistant', true);
      }
  }
}
