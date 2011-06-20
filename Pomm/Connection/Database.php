<?php
namespace Pomm\Connection;

use Pomm\Exception\Exception as PommException;
use Pomm\Tools\ParameterHolder;
use Pomm\Converter;

/**
 * Pomm\Connection\Database
 * 
 * @package Pomm
 * @version $id$
 * @copyright 2011 Grégoire HUBERT 
 * @author Grégoire HUBERT <hubert.greg@gmail.com>
 * @license X11 {@link http://opensource.org/licenses/mit-license.php}
 */
class Database 
{
  protected $parameter_holder = array();
  protected $_handler;
  protected $converters = array();

  /**
   * __construct 
   *
   * Parameters that can be sent :
   * dsn : an url like psql://user:pass@host:port/dbname
   * name : the connection name for this database (optionnal)
   * persistant : a boolean to use persistant connections or not (default true)
   * isolation : transaction isolation level (default READ COMMITED)
   *
   * @final
   * @param array $parameters 
   * @access public
   * @return void
   */
  final public function __construct($parameters = array())
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
      throw new PommException(sprintf('Cound not parse DSN "%s".', $dsn));
    }


    if ($matchs[1] == null)
    {
      throw new PommException(sprintf('No protocol information in dsn "%s".', $dsn));
    }
    $adapter = $matchs[1];

    if ($matchs[2] == null)
    {
      throw PommException(sprintf('No user information in dsn "%s".', $dsn));
    }
    $user = $matchs[2];
    $pass = $matchs[3];
    $host = $matchs[4];
    $port = $matchs[5];

    if ($matchs[6] == null)
    {
      throw new PommException(sprintf('No database name in dsn "%s".', $dsn));
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
      $this->registerBaseConverters();
  }

  /**
   * createTransaction()
   *
   * Opens a new connection to the database
   * This connection is transaction capable
   * @access public
   * @return Transaction
   **/
  public function createTransaction()
  {
      return new Transaction($this);
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
      return new Connection($this);
  }

  /**
   * registerConverter
   *
   * Register a new converter
   * @access public
   * @param name      string the name of the converter
   * @param converter ConverterInterface a converter instance
   * @param pg_types  Array an array of the mapped postgresql's types
   **/
  public function registerConverter($name, Converter\ConverterInterface $converter, Array $pg_types)
  {
      $this->converters[$name] = array('converter' => $converter, 'types' => $pg_types);
  }

  /**
   * getConverterFor
   *
   * Returns a converter
   * @access public
   * @param  string name
   * @return ConverterInterface converter
   **/
  public function getConverterFor($name)
  {
      return $this->converters[$name]['converter'];
  }

  /**
   * getConverterNameForType
   *
   * Returns the converter name for a given a postgresql's type
   * @access public
   * @param  string $pg_type
   * @return string converter name
   * @throw  Pomm\Exception\Exception if not found
   **/
  public function getConverterNameForType($pg_type)
  {
      foreach($this->converters as $name => $composite)
      {
          foreach($composite['types'] as $pattern)
          {
              if (preg_match("/$pattern/", $pg_type))
              {
                  return $name;
              }
          }
      }

      throw new PommException(sprintf("Could not find a converter for type '%s' declared for database type '%s' with dsn '%s'.", $pg_type, get_class($this), $this->parameter_holder['dsn']));
  }

  /**
   * getParameterHolder
   *
   * Returns the parameter holder
   * @acces public
   * @return ParameterHolder
   **/
  public function getParameterHolder()
  {
      return $this->parameter_holder;
  }

  /**
   * registerBaseConverters
   *
   * Register the converters for postgresql's built-in types
   * @access protected
   **/

  protected function registerBaseConverters()
  {
      $this->registerConverter('Boolean', new Converter\PgBoolean(), array('boolean'));
      $this->registerConverter('Integer', new Converter\PgInteger(), array('smallint', 'bigint', 'integer', 'decimal', 'numeric', 'real', 'double precision', 'serial', 'bigserial'));
      $this->registerConverter('String', new Converter\PgString(), array('character', 'text'));
      $this->registerConverter('Timestamp', new Converter\PgTimestamp(), array('timestamp', 'date', 'time'));
      $this->registerConverter('Point', new Converter\PgPoint(), array('point'));
  }
}
