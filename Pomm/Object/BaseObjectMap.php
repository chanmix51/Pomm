<?php

namespace Pomm\Object;

use \Pomm\Exception\Exception;
use \Pomm\Query\Where;
use \Pomm\Connection\Connection;
use \Pomm\Converter\PgRow;

/**
 * BaseObjectMap
 *
 * @abstract
 * @package Pomm
 * @uses Pomm\Exception\Exception
 * @uses Pomm\Query\Where
 * @uses Pomm\Connection\Connection
 * @version $id$
 * @copyright 2011-2013 Grégoire HUBERT
 * @author Grégoire HUBERT <hubert.greg@gmail.com>
 * @license MIT/X11 {@link http://opensource.org/licenses/mit-license.php}
 */
abstract class BaseObjectMap
{
    protected $connection;
    protected $object_class;
    protected $object_name;
    protected $row_structure;
    protected $virtual_fields = array();
    protected $pk_fields = array();
    protected $converter;

    /**
     * initialize
     *
     * This method is called by the constructor, use it to declare
     * - connection the connection name to use to query on this model objects
     * - fields_definitions (mandatory)
     * - object_class The class name of the corresponding model (mandatory)
     * - primary key (optional).
     *
     * @abstract
     * @access protected
     */
    abstract protected function initialize();

    /**
     * __construct
     *
     * This constructor is intended to be used by
     * \Pomm\Connection\Connection::getMapFor($class_name) to chain calls.
     *
     * @param \Pomm\Connection\Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        $this->row_structure = new RowStructure();
        $this->initialize();

        if (is_null($this->connection))
        {
            throw new Exception(sprintf('Postgresql connection not set after initializing db map "%s".', get_class($this)));
        }
        if (is_null($this->object_class))
        {
            throw new Exception(sprintf('Missing object_class after initializing db map "%s".', get_class($this)));
        }
        if (!$this->row_structure instanceOf RowStructure || count($this->row_structure->getFieldNames()) == 0)
        {
            throw new Exception(sprintf('No fields after initializing db map "%s", don\'t you prefer anonymous objects ?', get_class($this)));
        }

        $this->converter = new PgRow($this->connection->getDatabase(), $this->row_structure);
    }

    /**
     * getTableName
     *
     * Get the associated table signature (schema.name).
     *
     * @param  String $alias Optional alias (default null).
     * @return String
     */
    public function getTableName($alias = null)
    {
        $alias = is_null($alias) ? '' : sprintf(" %s", $alias);

        return sprintf("%s%s", $this->object_name, $alias);
    }

    /**
     * getObjectClass
     *
     * Returns object_class definition.
     *
     * @return String
     */
    public function getObjectClass()
    {
        return $this->object_class;
    }

    /**
     * getRowStructure
     *
     * Returns the underlying row structure
     *
     * @return RowStructure
     */
    public function getRowStructure()
    {
        return $this->row_structure;
    }

    /**
     * getConverter
     *
     * Returns the according PgRow
     *
     * @return PgRow
     */
    public function getConverter()
    {
        return $this->converter;
    }

    /**
     * getPrimaryKey
     *
     * Return the array with field names composing the PK.
     *
     * @return Array
     */
    public function getPrimaryKey()
    {
        return $this->pk_fields;
    }

    /**
     * addField
     *
     * Add a new field definition.
     *
     * @access protected
     * @param string $name
     * @param string $type Type must be associated with a converter
     * @return BaseObjectMap
     * @see Database::registerConverter()
     */
    protected function addField($name, $type)
    {
        $this->row_structure->addField($name, $type);

        return $this;
    }

    /**
     * addVirtualField
     *
     * Add a new virtual field definition.
     *
     * @access protected
     * @param string $name
     * @param string $type Type must be associated with a converter
     * @return BaseObjectMap
     * @see Database::registerConverter()
     */
    protected function addVirtualField($name, $type)
    {
        $this->virtual_fields[$name] = $type;

        return $this;
    }

    /**
     * createAndSaveObjects
     *
     * Create new instances in a single INSERT statement and return a
     * Collection from it. All values MUST have the same column set.
     *
     * @param Array Array of values.
     * @return Collection
     */
    public function createAndSaveObjects(Array $values_array)
    {
        if (count($values_array) == 0)
        {
            throw new Exception(sprintf("Empty array passed."));
        }

        $pg_values = array();

        foreach($values_array as $values)
        {
            $pg_values[] = $this->convertToPg($values);
        }

        $fields = array_keys($pg_values[1]);
        $sql = sprintf('INSERT INTO %s (%s) VALUES %s RETURNING %s;',
            $this->object_name,
            join(',', array_map(array($this->connection, 'escapeIdentifier'), $fields)),
            join(',', array_map(function ($tuple) { return sprintf("(%s)", join(', ', array_values($tuple))); }, $pg_values)),
            $this->formatFieldsWithAlias('getSelectFields')
        );

        return $this->query($sql);
    }

    /**
     * createAndSaveObject
     *
     * Create a new instance of the corresponding model class and save it in
     * the database.
     *
     * @param Array $values
     * @return BaseObject
     */
    public function createAndSaveObject(Array $values)
    {
        $object = $this->createObject($values);
        $this->saveOne($object);

        return $object;
    }

    /**
     * createObject
     *
     * Return a new instance of the corresponding model class.
     *
     * @param Array $values     Optional starting values.
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
     * create an object with converted values and check it against identity mapper
     * if any.
     *
     * @param Array $values  Values to be converted..
     * @return BaseObject $object
     */
    public function createObjectFromPg(Array $values)
    {
        return $this->makeObjectFromPg($this->convertFromPg($values));
    }

    public function makeObjectFromPg(Array $values)
    {
        $object = $this->createObject($values);
        $object->_setStatus(BaseObject::EXIST);

        if ($identity_map = $this->connection->getIdentityMapper())
        {
            $object = $identity_map->getInstance($object, $this->getPrimaryKey());
        }

        return $object;
    }

    /**
     * doQuery
     *
     * Send the query to the prepared statement system.
     *
     * @param String  $sql    SQL statement.
     * @param Array  $values Optional parameters for the prepared query.
     * @return Resource
     */
    public function doQuery($sql, $values = array())
    {
        return $this->connection->query($sql, $values);
    }

    /**
     * query
     *
     * Perform a query, hydrate the results and return a collection.
     *
     * @param String  $sql    SQL statement.
     * @param Array  $values Optional parameters for the prepared query.
     * @return \Pomm\Object\Collection
     */
    public function query($sql, $values = array())
    {
        return $this->createCollectionFromStatement($this->doQuery($sql, $values));
    }

    /**
     * queryPaginate
     *
     * paginate and execute a query.
     *
     * @param String  $sql       SQL query to paginate.
     * @param String  $sql_count SQL that count the overall results.
     * @param Array   $values    query parameters.
     * @param Integer $items_per_page
     * @param Integer $page      page index.
     * @return \Pomm\Object\Pager
     */
    public function paginateQuery($sql, $sql_count, $values, $items_per_page, $page = 1)
    {
        if ($page < 1)
        {
            throw new Exception(sprintf("Pagination offset (page) must be >= 1. ([%s] given).", $page));
        }

        $sql = sprintf("%s LIMIT %d OFFSET %d", $sql, $items_per_page, (int) ($items_per_page * ( $page - 1)));

        $collection = $this->query($sql, $values);
        $stmt = $this->doQuery($sql_count, $values);

        if (\pg_num_rows($stmt) > 1)
        {
            throw new Exception(sprintf("Count query '%s' return more than one field.", $sql_count));
        }

        return new Pager($collection, \pg_fetch_result($stmt, 0, 0), $items_per_page, $page);
    }

    /**
     * findAll
     *
     * The simplest query on a table.
     *
     * @param string $suffix
     *
     * @return \Pomm\Object\Collection
     */
    public function findAll($suffix = '')
    {
        return $this->query(sprintf('SELECT %s FROM %s %s;', $this->formatFieldsWithAlias('getSelectFields'), $this->object_name, $suffix), array());
    }

    /**
     * findWhere
     *
     * Performs a SQL query given conditions and parameters.
     *
     * @param String $where
     * @param Array  $values
     * @param String $suffix
     * @return \Pomm\Object\Collection
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
                throw new Exception(sprintf("findWhere expects a \\Pomm\\Query\\Where instance, '%s' given.", get_class($where)));
            }
        }

        return $this->query($this->generateSqlForWhere($where, $suffix), $values);
    }

    /**
     * paginateFindWhere
     *
     * Paginate and execute a query with a Where statement.
     *
     * @param Mixed    $where    Condition or Where instance.
     * @param Array    $values   Query parameters.
     * @param String   $suffix   Order by.
     * @param Integer  $items_per_page
     * @param Integer  $page     Page index.
     * @return \Pomm\Object\Pager
     */
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
                throw new Exception(sprintf("findWhere expects a \\Pomm\\Query\\Where instance, '%s' given.", get_class($where)));
            }
        }

        $sql_count = sprintf("SELECT count(*) FROM %s WHERE %s",
            $this->getTableName(),
            (string) $where
        );

        return $this->paginateQuery($this->generateSqlForWhere($where, $suffix), $sql_count, $values, $items_per_page, $page);
    }

    /**
     * findByPk
     *
     * Retrieve the corresponding entity from the database if it exists.
     *
     * @param Array $values Key value of the PK.
     * @return BaseObject
     */
    public function findByPk(Array $values)
    {
        if (count(array_diff(array_keys($values), $this->getPrimaryKey())) != 0)
        {
            throw new Exception(sprintf('Given values "%s" do not match PK definition "%s" using class "%s".', print_r($values, true), print_r($this->getPrimaryKey(), true), get_class($this)));
        }

        $result = $this->findWhere($this->createSqlAndFrom($values), array_values($values));

        return count($result) == 1 ? $result->current() : null;
    }

    /**
     * updateByPk
     *
     * Update and return a record given the PK.
     *
     * @param Array $pk
     * @param Array $values Values to be updated
     * @return BaseObject
     */
    public function updateByPk(Array $pk, Array $values)
    {
        $where = $this->createSqlAndFrom($pk);
        $converted_values = $this->convertToPg($values);
        $connection = $this->connection;

        $sql  = sprintf("UPDATE %s SET %s WHERE %s RETURNING %s",
            $this->getTableName(),
            join(', ', array_map(function($key, $value) use ($connection) { return sprintf("%s = %s", $connection->escapeIdentifier($key), $value); }, array_keys($converted_values), $converted_values)),
            (string) $where,
            $this->formatFieldsWithAlias('getSelectFields')
        );

        return $this
            ->query($sql, $where->getValues())
            ->current();
    }

    /**
     * deleteByPk
     *
     * Delete a record from the database given the PK.
     *
     * @param Array $pk
     * @return \Pomm\Object\Collection
     */
    public function deleteByPk(Array $pk)
    {
        $sql = sprintf('DELETE FROM %s WHERE %s RETURNING %s', $this->object_name, $this->createSqlAndFrom($pk), $this->formatFieldsWithAlias('getSelectFields'));

        return $this->query($sql, array_values($pk))->current();
    }

    /**
     * saveOne
     *
     * Use this to insert or update an object.
     *
     * @param BaseObject $object
     * @return BaseObject Saved instance.
     */
    public function saveOne(BaseObject &$object)
    {
        $this->checkObject($object, sprintf('"%s" class does not know how to save "%s" objects.', get_class($this), get_class($object)));

        if ($object->_getStatus() & BaseObject::EXIST)
        {
            $sql = sprintf('UPDATE %s SET %s WHERE %s RETURNING %s;', $this->object_name, $this->parseForUpdate($object), $this->createSqlAndFrom($object->get($this->getPrimaryKey())), $this->formatFieldsWithAlias('getSelectFields'));
            $collection = $this->query($sql, array_values($object->get($this->getPrimaryKey())));
        }
        else
        {
            $connection = $this->connection;
            $pg_values = $this->convertToPg($object->getFields());
            $sql = sprintf('INSERT INTO %s (%s) VALUES (%s) RETURNING %s;', $this->object_name, join(',', array_map(function($val) use ($connection) { return $connection->escapeIdentifier($val); }, array_keys($pg_values))), join(',', array_values($pg_values)), $this->formatFieldsWithAlias('getSelectFields'));

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
     *
     * Update part of an object.
     * Because this can trigger other changes in the database, the object is
     * reloaded and all other changes are discarded.
     *
     * @param BaseObject $object
     * @param Array                  $fields Only these fields will be updated.
     * @return BaseObject
     */
    public function updateOne(BaseObject &$object, Array $fields)
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
        foreach($this->convertToPg($values) as $field => $value)
        {
            $updates[] = sprintf("%s = %s", $this->connection->escapeIdentifier($field), $value);
        }

        $sql = sprintf("UPDATE %s SET %s WHERE %s RETURNING %s;",
            $this->object_name,
            join(', ', $updates),
            $this->createSqlAndFrom($object->get($this->getPrimaryKey())),
            $this->formatFieldsWithAlias('getSelectFields')
        );
        $collection = $this->query($sql, array_values($object->get($this->getPrimaryKey())));

        if ($collection->count())
        {
            $object = $collection->current();
        }

        $object->_setStatus(BaseObject::EXIST);
    }

    /**
     * deleteOne
     *
     * Delete the record tied with the given entity.
     *
     * @param BaseObject $object
     * @return BaseObject
     */
    public function deleteOne(BaseObject &$object)
    {
        $del_object = $this->deleteByPk($object->get($this->getPrimaryKey()));

        if ($del_object)
        {
            $object = $del_object;
        }

        $object->_setStatus(BaseObject::NONE);
    }

    /**
     * getGroupByFields
     *
     * When grouping by all fields, this returns the fields with
     * the given alias (default null).
     *
     * @see BaseObjectMap::getField()
     * @param String $alias Optional table alias prefix.
     * @return Array fields to be grouped.
     */
    public function getGroupByFields($alias = null)
    {
        return $this->getFields($alias);
    }

    /**
     * getSelectFields
     *
     * Get the list of fields to SELECT.
     * When selecting all fields, this is better than *.
     *
     * @see BaseObjectMap::getField()
     * @param String $alias the table alias in the query.
     * @return Array
     */
    public function getSelectFields($alias = null)
    {
        return $this->getFields($alias);
    }

    /**
     * getFields
     *
     * Get the list of field names.
     *
     * @final
     * @param String $alias the table alias in the query
     * @return Array
     */
    final public function getFields($alias = null)
    {
        $fields = array();

        foreach ($this->row_structure->getFieldNames() as $name)
        {
            $fields[$name] = $this->aliasField($this->connection->escapeIdentifier($name), $alias);
        }

        return $fields;
    }

    /**
     * aliasField
     *
     * Output alias.field if alias is provided.
     *
     * @param String $field
     * @param String $alias
     * @return String
     */
    public function aliasField($field, $alias)
    {
        return sprintf("%s%s", $alias != null ? $alias."." : "", $field);
    }

    /**
     * formatFieldsWithAlias
     *
     * This is used when queries need to format fields with column aliases.
     *
     * @param String This current map's getFields() method name.
     * @param String Optional table alias.
     * @return String
     */
    public function formatFieldsWithAlias($field_method, $table_alias = null)
    {
        if (!method_exists($this, $field_method))
        {
            throw new Exception(sprintf("'%s' method does not exist.", $field_method));
        }

        $fields = call_user_func(array($this, $field_method), $table_alias);
        $connection = $this->connection;

        return join(', ', array_map(function($name, $table_alias) use ($connection) { return sprintf("%s AS %s", $name, $connection->escapeIdentifier($table_alias)); }, $fields, array_keys($fields)));
    }

    /**
     * formatFields
     *
     * This is used when queries need formatted fields with no column alias.
     *
     * @param String This current map's getFields() method name.
     * @param String Optional table alias.
     * @return String
     */
    public function formatFields($field_method, $table_alias = null)
    {
        if (!method_exists($this, $field_method))
        {
            throw new Exception(sprintf("'%s' method does not exist.", $field_method));
        }

        return join(', ', call_user_func(array($this, $field_method), $table_alias));
    }

    /**
     * convertToPg
     *
     * Convert values to Postgresql.
     *
     * @param Array $values Values to convert.
     * @return Array Converted values to PG format.
     */
    public function convertToPg(Array $values)
    {
        return $this->converter->convertToPg($values);
    }

    /**
     * convertFromPg
     *
     * Convert values from Postgresql.
     *
     * @param Array $values Values from convert.
     * @return Array Converted values from PG format.
     */
    public function convertFromPg(Array $values)
    {
        $this->converter->setVirtualFields($this->virtual_fields);

        return $this->converter->convertFromPg($values);
    }

    /**
     * checkObject
     *
     * Check if the instance is from this map corresponding class or throw an
     * exception.
     *
     * @access protected
     * @param BaseObject $object
     * @param String                 $message  Will be set in the Exception.
     * @access protected
     */
    protected function checkObject(BaseObject $object, $message)
    {
        if (get_class($object) !== trim($this->object_class, "\\"))
        {
            throw new Exception(sprintf("check '%s' and '%s'. Context is «%s»", get_class($object), $this->object_class, $message));
        }
    }

    /**
     * createSqlAndFrom
     *
     * Create a SQL condition from the associative array with AND logical operator.
     *
     * @access protected
     * @param array $values
     * @return \Pomm\Query\Where
     */
    protected function createSqlAndFrom($values)
    {
        $where = new Where();
        foreach ($values as $key => $value)
        {
            $where->andWhere(sprintf('%s = $*', $key), array($value));
        }

        return $where;
    }

    /**
     * createCollectionFromStatement
     *
     * Creates a \Pomm\Object\Collection instance from a result resource.
     *
     * @access protected
     * @param resource $result
     * @return \Pomm\Object\Collection
     */
    protected function createCollectionFromStatement($result)
    {
        return new Collection($result, $this);
    }

    /**
     * generateSqlForWhere
     *
     * Generate the SQL for findWhere and paginateFindWhere methods.
     *
     * @see BaseObject::findWhere()
     * @see BaseObject::paginateFindWhere()
     * @access protected
     * @param Mixed  $where   Can be a String or \Pomm\Query\Where instance.
     * @param String $suffix  ORDER BY, LIMIT etc.
     * @return String   The SQL query.
     */
    protected function generateSqlForWhere($where, $suffix = null)
    {
        $sql = sprintf('SELECT %s FROM %s WHERE %s', $this->formatFieldsWithAlias('getSelectFields'), $this->object_name, $where);

        if (!is_null($suffix))
        {
            $sql = sprintf("%s %s", $sql, $suffix);
        }

        return $sql;
    }

    /**
     * parseForUpdate
     *
     * Format the converted values for UPDATE query.
     *
     * @access protected
     * @param  BaseObject $object
     * @return String
     */
    protected function parseForUpdate(BaseObject $object)
    {
        $tmp = array();

        foreach ($this->convertToPg($object->getFields()) as $field_name => $field_value)
        {
            if (array_key_exists($field_name, array_flip($this->getPrimaryKey()))) continue;

            $tmp[] = sprintf("%s=%s", $this->connection->escapeIdentifier($field_name), $field_value);
        }

        return implode(',', $tmp);
    }
}
