<?php

Namespace Pomm
/**
 * Pomm 
 * 
 * @package PommPlugin
 * @version $id$
 * @copyright 2011 Grégoire HUBERT 
 * @author Grégoire HUBERT <hubert.greg@gmail.com>
 * @license X11 {@link http://opensource.org/licenses/mit-license.php}
 */
class Pomm
{
    const VERSION = 'BETA - 1';
    static $connections = array();

    /**
     * createConnection 
     * save the connection in a static attribute
     * 
     * @param String name the connection name
     * @param Array parameters for PommDatabase
     * @static
     * @access public
     * @return void
     */
    public static function createConnection($name, Array $parameters)
    {
        self::$connections[$name] = new PommDatabase($parameters);
    }

    /**
     * getConnection 
     * Returns the corresponding PommDatabase or the first one if no name is provided
     * 
     * @param mixed $name 
     * @static
     * @access public
     * @return PommDatabase 
     */
    public static function getConnection($name = null)
    {
        if (is_null($name))
        {
            if (count(self::$connections) == 0)
            {
                throw new PommException(sprintf('No database connections.'));
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

        throw new PommException(sprintf('No database connection with this name "%s".', $name));
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
     * @return PommBaseObjectMap
     */
    public static function getMapFor($class)
    {
        $class_name = $class.'Map';

        return new $class_name();
    }
}
