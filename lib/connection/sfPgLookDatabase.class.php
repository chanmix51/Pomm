<?php

/**
 * sfPgLookDatabase 
 * 
 * @uses sfDatabase
 * @package sfPgLookPlugin
 * @version $id$
 * @copyright 2010 Grégoire HUBERT 
 * @author Grégoire HUBERT <hubert.greg@gmail.com>
 * @license MIT/X11 {@link http://opensource.org/licenses/mit-license.php}
 */
class sfPgLookDatabase extends sfDatabase
{
  protected $_handler;

  /**
   * __construct 
   * The constructor, see @sfDatabase
   *
   * @param array $parameters 
   * @access public
   * @return void
   */
  public function __construct($parameters = array())
  {
    parent::initialize($parameters);

    if (null !== $this->_handler)
    {
      return;
    }

    $this->processDsn();

    if (!$this->hasParameter('persistant'))
    {
      $this->setParameter('persistant', false);
    }
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
      throw new sfConfigurationException(sprintf('Cound not parse DSN "%s".', $dsn));
    }


    if ($matchs[1] == null)
    {
      throw sfConfigurationException(sprintf('No protocol information in dsn "%s".', $dsn));
    }
    $adapter = $matchs[1];

    if ($matchs[2] == null)
    {
      throw sfConfigurationException(sprintf('No user information in dsn "%s".', $dsn));
    }
    $user = $matchs[2];
    $pass = $matchs[3];

    if ($matchs[4] == null)
    {
      throw sfConfigurationException(sprintf('No hostname name in dsn "%s".', $dsn));
    }
    $host = $matchs[4];
    $port = $matchs[5];

    if ($matchs[6] == null)
    {
      throw sfConfigurationException(sprintf('No database name in dsn "%s".', $dsn));
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
   * see @sfDatabase
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
      $this->_handler = new PDO($connect_string);
    }
    catch (PDOException $e)
    {
      throw new PgLookException(sprintf('Error connecting to the database with dsn «%s». Driver said "%s".', $connect_string, $e->getMessage()));
    }
  }

  /**
   * shutdown 
   * see @sfDatabase
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
}
