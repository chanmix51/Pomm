<?php

namespace Pomm;

use Pomm\Connection\Database;
use Pomm\Exception\Exception;

/**
 * Pomm 
 * 
 * @package PommBundle
 * @version $id$
 * @copyright 2011 Grégoire HUBERT 
 * @author Grégoire HUBERT <hubert.greg@gmail.com>
 * @license X11 {@link http://opensource.org/licenses/mit-license.php}
 */
class Pomm
{
    const VERSION = 'BETA - 1';

    static protected $databases = array();

    /**
     * setDatabase
     * save the Database in a static attribute
     *
     * @static
     * @access public
     * @param String name the database name
     * @param Array parameters for Database
     * @return void
     */
    static public function setDatabase($name, Array $parameters)
    {
        self::$databases[$name] = new Database($parameters);
    }

    /**
     * getDatabase 
     * Returns the corresponding PommDatabase or the first one if no name is provided
     * 
     * @param mixed $name 
     * @static
     * @access public
     * @return PommDatabase 
     */
    static public function getDatabase($name = null)
    {
        if (is_null($name))
        {
            if (count(self::$databases) == 0)
            {
                throw new Exception(sprintf('No database registered.'));
            }
            else
            {
                $db = array_values(self::$databases);
                return $db[0];
            }
        }
        if (array_key_exists($name, self::$databases))
        {
            return self::$databases[$name];
        }

        throw new Exception(sprintf('No database with this name "%s".', $name));
    }

    /**
     * executeAnonymousSelect 
     * Performs a raw SQL query
     * 
     * @param string $sql 
     * @param string $database 
     * @static
     * @access public
     * @return PDOStatement
     */
    public static function executeAnonymousQuery($sql, $database = null)
    {
        return self::getDatabase($database)->createConnection()->getPdo()->query($sql, \PDO::FETCH_LAZY);
    }
}
