<?php

/**
 * PgLook 
 * 
 * @package sfPgLookPlugin
 * @version $id$
 * @copyright 2010 Grégoire HUBERT 
 * @author Grégoire HUBERT <hubert.greg@gmail.com>
 * @license X11 {@link http://opensource.org/licenses/mit-license.php}
 */
class PgLook
{
  const VERSION = 'BETA - 1';
  static $connections = array();

  /**
   * saveConnections 
   * save the connection as static attribute
   * 
   * @param sfDatabaseManager $db_manager 
   * @static
   * @access public
   * @return void
   */
  public static function saveConnections(sfDatabaseManager $db_manager)
  {
    foreach ($db_manager->getNames() as $name)
    {
      if ($db_manager->getDatabase($name) instanceof sfPgLookDatabase)
      {
        self::$connections[$name] = $db_manager->getDatabase($name);
      }
    }
  }

  /**
   * setConnectionsEvent 
   * When the factories are loaded, we save the sfPgLookDatabases 
   * 
   * @param sfEvent $event 
   * @static
   * @access public
   * @return void
   */
  public static function setConnectionsEvent(sfEvent $event)
  {
    self::saveConnections($event->getSubject()->getDatabaseManager());
  }

  /**
   * getConnection 
   * Returns the corresponding sfPgLookDatabase or the first one if no name is provided
   * 
   * @param mixed $name 
   * @static
   * @access public
   * @return sfPgLookDatabase 
   */
  public static function getConnection($name = null)
  {
    if (is_null($name))
    {
      if (count(self::$connections) == 0)
      {
        throw new PgLookException(sprintf('No database connections.'));
      }
      else
      {
        $cnx = array_values(self::$connections);
        return $cnx[0];
      }
    }
    if (array_key_exists($name, self::$connections))
    {
      return self::$connections[$name];
    }

    throw new PgLookException(sprintf('No database connection with this name "%s".', $name));
  }

  /**
   * executeAnonymousSelect 
   * Performs a raw SQL query
   * 
   * @param string $sql 
   * @param string $connection 
   * @static
   * @access public
   * @return PDOStatement
   */
  public static function executeAnonymousQuery($sql, $connection = null)
  {
    return self::getConnection($connection)->getPdo()->query($sql, PDO::FETCH_LAZY);
  }

  /**
   * getMapFor 
   * Returns a Map instance of the given model name
   * 
   * @param string $class 
   * @static
   * @access public
   * @return PgLookBaseObjectMap
   */
  public static function getMapFor($class)
  {
    $class_name = $class.'Map';

    return new $class_name();
  }
}
