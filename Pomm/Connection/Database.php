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
 * @copyright 2011 - 2013 Grégoire HUBERT
 * @author Grégoire HUBERT <hubert.greg@gmail.com>
 * @license X11 {@link http://opensource.org/licenses/mit-license.php}
 */
class Database
{
    protected $parameter_holder = array();
    protected $_handler;
    protected $converters = array();
    protected $handled_types = array();
    protected $connection;

    /**
     * __construct
     *
     * Parameters that can be sent :
     * dsn : an url like psql://user:pass@host:port/dbname
     * name : the connection name for this database (optional)
     * persistant : a boolean to use persistant connections or not (default true)
     * isolation : transaction isolation level (default READ COMMITED)
     *
     * @final
     * @param  Array $parameters
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

        if (!preg_match('#([a-z]+)://([^:@]+)(?::([^@]+))?(?:@([\w\.-]+|!/.+[^/]!)(?::(\w+))?)?/(.+)#', $dsn, $matchs))
        {
            throw new PommException(sprintf('Could not parse DSN "%s".', $dsn));
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

        if (preg_match('/!(.*)!/', $matchs[4], $host_matchs))
        {
            $host = $host_matchs[1];
        }
        else
        {
            $host = $matchs[4];
        }

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
     * overloaded.
     *
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
     * createConnection()
     *
     * Opens a new connection to the database and stores it. It overrides any previous connection.
     * @access public
     * @param  \Pomm\Identity\IdentityMapperInterface $mapper An optional instance of a data mapper.
     * @return \Pomm\Connection\Connection
     */
    public function createConnection(\Pomm\Identity\IdentityMapperInterface $mapper = null)
    {
        $this->connection = new Connection($this, $mapper);

        return $this->connection;
    }

    /**
     * getConnection
     *
     * Returns the opened connection if any. If no connection is opened yet, it
     * creates a new one with default parameters.
     * @access public
     * @return \Pomm\Connection\Connection
     */
    public function getConnection()
    {
        if (is_null($this->connection))
        {
            return $this->createConnection();
        }

        return $this->connection;
    }

    /**
     * registerConverter
     *
     * Register a new converter
     * @access public
     * @param  String             $name      The name of the converter.
     * @param  \Pomm\Converter\ConverterInterface $converter A converter instance.
     * @param  Array              $pg_types  An array of the mapped postgresql's types.
     * @return \Pomm\Connection\Database
     */
    public function registerConverter($name, Converter\ConverterInterface $converter, Array $pg_types)
    {
        $this->converters[$name] = $converter;

        foreach ($pg_types as $type)
        {
            $this->handled_types[$type] = $name;
        }

        return $this;
    }

    /**
     * getConverterFor
     *
     * Returns a converter from its designation.
     *
     * @access public
     * @param  string $name       Converter designation.
     * @return \Pomm\Converter\ConverterInterface Converter instance.
     */
    public function getConverterFor($name)
    {
        return $this->converters[$name];
    }

    /**
     * getConverterForType
     *
     * Returns the converter instance for a given a postgresql's type
     *
     * @access public
     * @param  String $pg_type Type name.
     * @return String Converter instance.
     * @throw  \Pomm\Exception\Exception if not found.
     */
    public function getConverterForType($pg_type)
    {
        if (isset($this->handled_types[$pg_type]))
        {
            $converter_name = $this->handled_types[$pg_type];

            if (isset($this->converters[$converter_name]))
            {
                return $this->converters[$converter_name];
            }
            else
            {
                throw new PommException(sprintf("Pg type '%s' is associated with converter '%s' but converter is not registered.", $pg_type, $converter_name));
            }
        }

        throw new PommException(sprintf("Could not find a converter for type '%s'.", $pg_type));
    }

    /**
     * registerTypeForConverter
     *
     * Associate an existing converter with a Pg type.
     * This is useful for DOMAINs.
     *
     * @access public
     * @param String $type           Type name
     * @param String $converter_name Converter designation.
     * @return \Pomm\Connection\Database
     */
    public function registerTypeForConverter($type, $converter_name)
    {
        $this->handled_types[$type] = $converter_name;

        return $this;
    }

    /**
     * getParameterHolder
     *
     * Returns the parameter holder
     *
     * @access public
     * @return ParameterHolder
     */
    public function getParameterHolder()
    {
        return $this->parameter_holder;
    }

    /**
     * registerBaseConverters
     *
     * Register the converters for postgresql's built-in types
     *
     * @access protected
     */

    protected function registerBaseConverters()
    {
        $this->registerConverter('Array', new Converter\PgArray($this), array());
        $this->registerConverter('Boolean', new Converter\PgBoolean(), array('bool'));
        $this->registerConverter('Number', new Converter\PgNumber(), array('int2', 'int4', 'int8', 'numeric', 'float4', 'float8'));
        $this->registerConverter('String', new Converter\PgString(), array('varchar', 'char', 'text', 'uuid', 'tsvector', 'xml', 'bpchar', 'json', 'name'));
        $this->registerConverter('Timestamp', new Converter\PgTimestamp(), array('timestamp', 'date', 'time'));
        $this->registerConverter('Interval', new Converter\PgInterval(), array('interval'));
        $this->registerConverter('Binary', new Converter\PgBytea(), array('bytea'));
        $this->registerConverter('NumberRange', new Converter\PgNumberRange(), array('int4range', 'int8range', 'numrange'));
        $this->registerConverter('TsRange', new Converter\PgTsRange(), array('tsrange', 'daterange'));
    }

    /**
     * getName
     *
     * Returns the database name.
     * This name is used to generate the namespaces for the Model files.
     *
     * @access public
     * @return String
     */
    public function getName()
    {
        return $this->parameter_holder->hasParameter('name') ? $this->parameter_holder['name'] : $this->parameter_holder['database'];
    }

    /**
     * setName
     *
     * Sets the database name.
     *
     * @access public
     * @param String $name Database name
     */
    public function setName($name)
    {
        $this->parameter_holder->setParameter('name', $name);
    }
}
