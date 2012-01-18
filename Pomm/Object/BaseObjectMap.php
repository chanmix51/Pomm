<?php
namespace Pomm\Object;

use Pomm\Exception\Exception;
use Pomm\Exception\SqlException;
use Pomm\Query\Where;
use Pomm\Connection\Connection;
use Pomm\Type as Type;

/**
 * BaseObjectMap 
 * 
 * @abstract
 * @package Pomm
 * @version $id$
 * @copyright 2011 Grégoire HUBERT 
 * @author Grégoire HUBERT <hubert.greg@gmail.com>
 * @license MIT/X11 {@link http://opensource.org/licenses/mit-license.php}
 */
abstract class BaseObjectMap
{
    protected $connection;
    protected $object_class;
    protected $object_name;
    protected $field_definitions = array();
    protected $pk_fields = array();

    /**
     * initialize 
     * This method is called by the constructor, use it to declare
     * - connection the connection name to use to query on this model objects
     * - fields_definitions (mandatory)
     * - object_class The class name of the corresponding model (mandatory)
     * - primary key (optional)
     * 
     * @abstract
     * @access protected
     * @return void
     */
    abstract protected function initialize();

    /**
     * addField 
     * Add a new field definition
     *
     * @param string $name 
     * @param string $type 
     * @access protected
     * @return void
     */
    protected function addField($name, $type)
    {
        if (array_key_exists($name, $this->field_definitions))
        {
            throw new Exception(sprintf('Field "%s" already set in class "%s".', $name, get_class($this)));
        }

        $this->field_definitions[$name] = $type;
    }

    /**
     * createObject 
     * Return a new instance of the corresponding model class
     * 
     * @access public
     * @return BaseObject
     */
    public function createObject(Array $values = null)
    {
        $class_name = $this->object_class;

        return new $class_name($values);
    }

    /**
     * createObjectFromPg
     *
     * create an object with converted values
     * @param Array $values that will be converted
     * @return \Pomm\Object\BaseObject $object
     **/
    public function createObjectFromPg(Array $values)
    {
        $values = $this->convertPg($values, 'fromPg');
        $object = $this->createObject($values);
        $object->_setStatus(BaseObject::EXIST);

        $identity_map = $this->connection->getIdentityMapper();

        return $identity_map ? $identity_map->getModelInstance($object, $this->getPrimaryKey()) : $object;
    }

    /**
     * getFieldDefinitions 
     * Return the field definitions of the current model
     * 
     * @access public
     * @return Array(string)
     */
    public function getFieldDefinitions()
    {
        return $this->field_definitions;
    }

    /**
     * __construct 
     * The constructor. Most of the time, you should use Pomm\Connection\Connection::getMapFor($class_name) to chain calls
     * 
     * @access public
     * @return void
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        $this->initialize();

        if (is_null($this->connection))
        {
            throw new Exception(sprintf('PDO connection not set after initializing db map "%s".', get_class($this)));
        }
        if (is_null($this->object_class))
        {
            throw new Exception(sprintf('Missing object_class after initializing db map "%s".', get_class($this)));
        }
        if (count($this->field_definitions) == 0)
        {
            throw new Exception(sprintf('No fields after initializing db map "%s", don\'t you prefer anonymous objects ?', get_class($this)));
        }
    }

    /**
     * prepareStatement 
     * Prepare a SQL statement
     * 
     * @param string $sql 
     * @access protected
     * @return PDOStatement
     */
    protected function prepareStatement($sql)
    {
        return $this->connection->getPdo()->prepare($sql, array(\PDO::CURSOR_SCROLL));
    }

    /**
     * bindParams 
     * Bind parameters to a prepared statement
     * 
     * @param PDOStatement $stmt 
     * @param mixed $values 
     * @access protected
     * @return PDOStatement
     */
    protected function bindParams($stmt, $values)
    {
        foreach ($values as $pos => $value)
        {
            if (is_integer($value))
            {
                $type = \PDO::PARAM_INT;
            }
            elseif (is_bool($value))
            {
                $type = \PDO::PARAM_BOOL;
            }
            else
            {
                $type = null;
            }

            if (is_null($type))
            {
                $stmt->bindValue($pos + 1, $value);
            }
            else
            {
                $stmt->bindValue($pos + 1, $value, $type);
            }
        }

        return $stmt;
    }

    /**
     * doQuery 
     * Performs a query, returns the PDO Statment instance used
     * 
     * @param string $sql 
     * @param mixed $values 
     * @access protected
     * @return PDOStatement
     */
    protected function doQuery($sql, $values = array())
    {
        $logger = $this->connection->getDatabase()->getLogger();
        if ($logger)
        {
            $logger->startQuery($sql, $values);
        }

        $stmt = $this->prepareStatement($sql);
        $this->bindParams($stmt, $values);
        try
        {
            if (!$stmt->execute())
            {
                throw new SqlException($stmt, $sql);
            }
        }
        catch(\PDOException $e)
        {
            throw new Exception('PDOException while performing SQL query «%s». The driver said "%s".', $sql, $e->getMessage());
        }

        if ($logger)
        {
            $logger->stopQuery();
        }

        return $stmt;
    }

    /**
     * query 
     * Perform a query, hydrate the results and return a collection
     * 
     * @param string $sql 
     * @param mixed $values 
     * @access public
     * @return Collection
     */
    public function query($sql, $values = array())
    {
        return $this->createCollectionFromStatement($this->doQuery($sql, $values));
    }

    /**
     * createSqlAndFrom 
     * Create a SQL condition from the associative array with AND logical operator
     * 
     * @param array $values 
     * @access protected
     * @return string
     */
    protected function createSqlAndFrom($values)
    {
        $where = new Where();
        foreach ($values as $key => $value)
        {
            $where->andWhere(sprintf('%s = ?', $key), array($value));
        }

        return $where;
    }

    /**
     * createObjectsFromStmt 
     * 
     * @param PDOStatement $stmt 
     * @access protected
     * @return Collection
     */
    protected function createCollectionFromStatement(\PDOStatement $stmt)
    {
        return new Collection($stmt, $this);
    }

    /**
     * findAll 
     * The simplest query on a table
     * 
     * @access public
     * @return Collection
     */
    public function findAll()
    {
        return $this->query(sprintf('SELECT %s FROM %s;', join(', ', $this->getSelectFields()), $this->object_name), array());
    }

    /**
     * generateSqlForWhere
     * Generate the SQL for findWhere and paginateFindWhere methods
     *
     * @see findWhere
     * @see paginateFindWhere
     **/
    protected function generateSqlForWhere($where, $suffix = null)
    {
        $sql = sprintf('SELECT %s FROM %s WHERE %s', join(', ', $this->getSelectFields()), $this->object_name, $where); 

        if (!is_null($suffix)) 
        {
            $sql = sprintf("%s %s", $sql, $suffix);
        }

        return $sql;
    }

    /**
     * findWhere 
     * 
     * @param string $where 
     * @param array $values 
     * @param sring $suffix
     * @access public
     * @return Collection
     */
    public function findWhere($where, $values = array(), $suffix = null)
    {
        if (is_object($where))
        {
            if ($where instanceof Where)
            {
                $values = $where->getValues();
            }
            else
            {
                throw new Exception(sprintf("findWhere expects a Pomm\\Query\\Where instance, '%s' given.", get_class($where)));
            }
        }

        return $this->query($this->generateSqlForWhere($where, $suffix), $values);
    }

    /**
     * getPrimaryKey 
     * 
     * @access public
     * @return array
     */
    public function getPrimaryKey()
    {
        return $this->pk_fields;
    }

    /**
     * findByPk 
     * 
     * @param Array $values 
     * @access public
     * @return BaseObject
     */
    public function findByPk(Array $values)
    {
        if (count(array_diff(array_keys($values), $this->getPrimaryKey())) != 0)
        {
            throw new Exception(sprintf('Given values "%s" do not match PK definition "%s" using class "%s".', print_r($values, true), print_r($this->getPrimaryKey(), true), get_class($this)));
        }

        if ($identity_mapper = $this->connection->getIdentityMapper() and
            ($object = $identity_mapper->checkModelInstance($this->object_class, $values)))
        {
            return $object;
        }

        $result = $this->findWhere($this->createSqlAndFrom($values), array_values($values));

        return count($result) == 1 ? $result->current() : null;
    }

    /**
     * convertPg 
     * Convert values to and from Postgresql
     *
     * @param Array $values Values to convert
     * @param mixed $method can be "fromPg" and "toPg"
     * @access protected
     * @return array
     */
    protected function convertPg(Array $values, $method)
    {
        $out_values = array();
        foreach ($values as $name => $value)
        {
            if (is_null($value)) continue;

            $converter_name = array_key_exists($name, $this->field_definitions) ? $this->field_definitions[$name] : null;

            if (is_null($converter_name))
            {
                if ($method == 'fromPg')
                {
                    $out_values[$name] = $value;
                }

                continue;
            }

            if (preg_match('/([a-z]+)(\[\])?/i', $converter_name, $matchs))
            {
                if (count($matchs) <= 2)
                {
                    $field_value = $this->connection->getDatabase()->getConverterFor($converter_name)->$method($value);
                }
                else
                {
                    $converter_name = $matchs[1];
                    if ($method === 'fromPg')
                    {
                        $field_value = array();
                        $value = trim($value, "{}");
                        foreach(preg_split('/[,\s]*"([^"]+)"[,\s]*|[,\s]+/', $value, 0, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE) as $elt)
                        {
                            $field_value[] = $this->connection->getDatabase()->getConverterFor($converter_name)->$method($elt);
                        }
                    }
                    else
                    {
                        $converter = $this->connection->getDatabase()->getConverterFor($converter_name);
                        array_walk($value, function(&$a) use ($converter) { $a = $converter->toPg($a);});
                        $field_value = sprintf("ARRAY[%s]", join(', ', $value));
                    }
                }
            }
            else
            {
                throw new Exception(sprintf('Error, bad type converter expression "%s".', $converter));
            }

            $out_values[$name] = $field_value;
        }

        return $out_values;
    }

    /**
     * checkObject 
     * Check if the instance is from the expected class or throw an exception
     *
     * @param BaseObject $object 
     * @param string $message 
     * @access protected
     * @return void
     */
    protected function checkObject(BaseObject $object, $message)
    {
        if (get_class($object) !== $this->object_class)
        {
            throw new Exception(sprintf("check '%s' and '%s'. Context is «%s»", get_class($object), $this->object_class, $message));
        }
    }

    /**
     * deleteByPk 
     * 
     * @param Array $pk 
     * @access public
     * @return Collection
     */
    public function deleteByPk(Array $pk)
    {
        $sql = sprintf('DELETE FROM %s WHERE %s RETURNING %s', $this->object_name, $this->createSqlAndFrom($pk), join(', ', $this->getSelectFields()));

        return $this->query($sql, array_values($pk));
    }

    /**
     * saveOne 
     * Save an instance. Use this to insert or update an object
     *
     * @param BaseObject $object 
     * @access public
     */
    public function saveOne(BaseObject &$object)
    {
        $this->checkObject($object, sprintf('"%s" class does not know how to save "%s" objects.', get_class($this), get_class($object)));

        if ($object->_getStatus() & BaseObject::EXIST)
        {
            $sql = sprintf('UPDATE %s SET %s WHERE %s RETURNING %s;', $this->object_name, $this->parseForUpdate($object), $this->createSqlAndFrom($object->get($this->getPrimaryKey())), join(', ', $this->getSelectFields()));

            $collection = $this->query($sql, array_values($object->get($this->getPrimaryKey())));
        }
        else
        {
            $pg_values = $this->parseForInsert($object);
            $sql = sprintf('INSERT INTO %s (%s) VALUES (%s) RETURNING %s;', $this->object_name, join(',', array_keys($pg_values)), join(',', array_values($pg_values)), join(', ', $this->getSelectFields()));

            $collection = $this->query($sql, array());
        }

        if ($collection->count())
        {
            $object = $collection->current();
        }

        $object->_setStatus(BaseObject::EXIST);
    }

    /**
     * updateOne
     * Update part of an object
     * Because this can trigger other changes in the database, the object is 
     * reloaded and all other changes in it will be discarded
     *
     * @param BaseObject 
     * @param Array fields
     **/
    public function updateOne(BaseObject $object, Array $fields)
    {
        $this->checkObject($object, sprintf('"%s" class does not know how to update "%s" objects.', get_class($this), get_class($object)));

        if (!$object->_getStatus() & BaseObject::EXIST)
        {
            throw new Exception(sprintf("Object class '%s' seems not to exist in the database, try 'saveOne' method instead.", get_class($object)));
        }

        $values = array();
        foreach($fields as $field)
        {
            if (array_key_exists($field, array_flip($this->getPrimaryKey())))
            {
                throw new Exception(sprintf("Field '%s' to be updated belongs to the primary key of table '%s'. Do that directly with the map class instead of using a BaseObject.", $field, $this->object_name));
            }

            $values[$field] = $object->get($field);
        }

        $updates = array();
        foreach($this->convertPg($values, 'toPg') as $field => $value)
        {
            $updates[] = sprintf("%s = %s", $field, $value);
        }



        $sql = sprintf("UPDATE %s SET %s WHERE %s RETURNING %s;", $this->object_name, join(', ', $updates), $this->createSqlAndFrom($object->get($this->getPrimaryKey())), join(', ', $this->getSelectFields()));
        $collection = $this->query($sql, array_values($object->get($this->getPrimaryKey())));

        if ($collection->count())
        {
            $object = $collection->current();
        }

        $object->_setStatus(BaseObject::EXIST);
    }

    /**
     * parseForInsert 
     * 
     * @param BaseObject $object 
     * @access protected
     * @return array
     */
    protected function parseForInsert($object)
    {
        $tmp = array();
        foreach ($this->convertPg($object->extract(), 'toPg') as $field_name => $field_value)
        {
            $tmp[$field_name] = $field_value;
        }

        return $tmp;
    }

    /**
     * parseForUpdate 
     * 
     * @param BaseObject $object 
     * @access protected
     * @return string
     */
    protected function parseForUpdate($object)
    {
        $tmp = array();

        foreach ($this->convertPg($object->extract(), 'toPg') as $field_name => $field_value)
        {
            if (array_key_exists($field_name, array_flip($this->getPrimaryKey()))) continue;
            $tmp[] = sprintf('%s=%s', $field_name, $field_value);
        }

        return implode(',', $tmp);
    }

    /**
     * hasField 
     * Does this class have the given field
     * 
     * @param string $field 
     * @access public
     * @return boolean
     */
    public function hasField($field)
    {
        return array_key_exists($field, $this->field_definitions);
    }

    /**
     * getTableName 
     * 
     * @access public
     * @param  string optionnal alias (default null)
     * @return string
     */
    public function getTableName($alias = null)
    {
        $alias = is_null($alias) ? '' : sprintf(" %s", $alias);

        return sprintf("%s%s", $this->object_name, $alias);
    }

    /**
     * deleteOne 
     * 
     * @param BaseObject $object 
     * @access public
     * @return void
     */
    public function deleteOne(BaseObject $object)
    {
        $collection = $this->deleteByPk($object->get($this->getPrimaryKey()));

        if ($collection->count != 0)
        {
            $object = $collection->current();
        }

        $object->_setStatus(BaseObject::NONE);
    }

    /**
     * getGroupByFields
     *
     * When grouping by all fields, this returns the fields with 
     * the given alias (default empty string).
     *
     * @param String $alias the table alias in the query
     * @access public
     * @return Array
     **/
    public function getGroupByFields($alias = null)
    {
        return $this->getFields($alias);
    }

    /**
     * getSelectFields
     *
     * When selecting all fields, this is better than *
     *
     * @param String $alias the table alias in the query
     * @access public
     * @return Array
     **/
    public function getSelectFields($alias = null)
    {
        return $this->getFields($alias);
    }

    /**
     * getFields
     *
     * When selecting all fields, this is better than *
     *
     * @param String $alias the table alias in the query
     * @access public
     * @return Array
     **/
    public function getFields($alias = null)
    {
        $fields = array();
        $alias  = is_null($alias) ? '' : $alias.".";

        foreach ($this->field_definitions as $name => $type)
        {
            $fields[] = sprintf("%s%s", $alias, $name);
        }

        return $fields;
    }

    /**
     * getRemoteSelectFields
     * Return the select fields formatted as table{%s} for use with 
     * createFromForeign filter.
     *
     * @param String $alias
     * @return Array $fields
     **/
    public function getRemoteSelectFields($alias = null)
    {
        $fields = $this->getSelectFields();
        $alias = is_null($alias) ? '' : sprintf("%s.", $alias);
        $table = $this->getTableName();
        $table = strpos($table, '.') ? substr(strstr($table, '.'), 1) : $table;

        return array_map(function($field) use ($table, $alias) { 
            return sprintf('%s AS "%s{%s}"', 
                $alias.$field,
                $table,
                $field
            ); }, $fields);
    }

    /**
     * createFromForeign
     * Hydrate an object from the values with keys formated like table{field}
     * and set it in the values with the table name as key. All the values used 
     * to hydrate the object are removed from the array.
     *
     * @param Array values
     * @return Array values
     **/
    public function createFromForeign(Array $old_values)
    {
        $values = array();
        $new_values = array();
        $table = $this->getTableName();
        $table_name = strpos($table, '.') ? substr(strstr($table, '.'), 1) : $table;

        foreach($old_values as $name => $value)
        {
            if (preg_match(sprintf('/%s\{(\w+)\}/', $table_name), $name, $matchs))
            {
                $values[$matchs[1]] = $value;
            }
            else
            {
                $new_values[$name] = $value;
            }
        }

        $new_values[$table_name] = $this->createObjectFromPg($values);

        return $new_values;
    }

    /**
     * queryPaginate
     * paginate and execute a query
     *
     * @param $sql the SQL query to paginate
     * @param $sql_count the SQL that count the overall results (whatever the column name is)
     * @param $values the queries parameters
     * @param $items_per_page how many results per page
     * @param $page the page index
     *
     * @return Pager
     **/
    public function paginateQuery($sql, $sql_count, $values, $items_per_page, $page = 1)
    {
        if ($page < 1)
        {
            throw new Exception(sprintf("Pagination offset (page) must be >= 1. ([%s] given).", $page));
        }

        $sql = sprintf("%s LIMIT %d OFFSET %d", $sql, $items_per_page, (int) ($items_per_page * ( $page - 1)));

        $collection = $this->query($sql, $values);
        $stmt = $this->doQuery($sql_count, $values);

        if ($stmt->columnCount() > 1)
        {
            throw new Exception(sprintf("Count query '%s' return more than one field.", $sql_count));
        }

        return new Pager($collection, $stmt->fetchColumn(), $items_per_page, $page);
    }

    /**
     * paginateFindWhere
     * Paginate and execute a query with a Where statement
     *
     * @param $where the where string or Where instance
     * @param $values the queries parameters
     * @param $suffix any ORDER BY or others
     * @param $items_per_page how many results per page
     * @param $page the page index
     **/
    public function paginateFindWhere($where, $values, $suffix, $items_per_page, $page = 1)
    {
        if (is_object($where))
        {
            if ($where instanceof Where)
            {
                $values = $where->getValues();
            }
            else
            {
                throw new Exception(sprintf("findWhere expects a Pomm\\Query\\Where instance, '%s' given.", get_class($where)));
            }
        }

        $sql_count = sprintf("SELECT count(*) FROM %s WHERE %s", 
            $this->getTableName(),
            (string) $where
        );

        return $this->paginateQuery($this->generateSqlForWhere($where, $suffix), $sql_count, $values, $items_per_page, $page);
    }

}
