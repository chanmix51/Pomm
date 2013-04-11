<?php
namespace Pomm\Object;

use \Pomm\Exception\Exception;
use \Pomm\Exception\SqlException;
use \Pomm\Query\Where;
use \Pomm\Connection\Connection;
use \Pomm\Type as Type;

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
    protected $virtual_fields = array();
    protected $pk_fields = array();

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
     * getFieldDefinitions 
     *
     * Return the field definitions of the current model.
     * 
     * @return Array    Types associated with each field.
     */
    public function getFieldDefinitions()
    {
        return $this->field_definitions;
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
     * hasField 
     *
     * Does this class have the given field.
     * 
     * @param  String $field Fields name.
     * @return Boolean
     */
    public function hasField($field)
    {
        return array_key_exists($field, $this->field_definitions);
    }

    /**
     * addField 
     *
     * Add a new field definition.
     *
     * @access protected
     * @param string $name
     * @param string $type Type must be associated with a converter
     * @see Database::registerConverter()
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
     * addVirtualField 
     *
     * Add a new virtual field definition.
     *
     * @access protected
     * @param string $name 
     * @param string $type Type must be associated with a converter
     * @see Database::registerConverter()
     */
    protected function addVirtualField($name, $type)
    {
        $this->virtual_fields[$name] = $type;
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
        $values = $this->convertFromPg($values);
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
     * Execute the filterChain.
     *
     * @param String  $sql    SQL statement.
     * @param Array  $values Optional parameters for the prepared query.
     * @return \PDOStatement
     */
    public function doQuery($sql, $values = array())
    {
        return $this->connection->executeFilterChain($this, $sql, $values);
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

        if ($stmt->columnCount() > 1)
        {
            throw new Exception(sprintf("Count query '%s' return more than one field.", $sql_count));
        }

        return new Pager($collection, $stmt->fetchColumn(), $items_per_page, $page);
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

        $sql  = sprintf("UPDATE %s SET %s WHERE %s RETURNING %s",
            $this->getTableName(),
            join(', ', array_map(function($key, $value) { return sprintf("\"%s\" = %s", $key, $value); }, array_keys($converted_values), $converted_values)),
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
            $pg_values = $this->convertToPg($object->getFields());
            $sql = sprintf('INSERT INTO %s (%s) VALUES (%s) RETURNING %s;', $this->object_name, join(',', array_map(function($val) { return sprintf('"%s"', $val); }, array_keys($pg_values))), join(',', array_values($pg_values)), $this->formatFieldsWithAlias('getSelectFields'));

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
            $updates[] = sprintf("\"%s\" = %s", $field, $value);
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
     * @param String $alias the table alias in the query
     * @return Array
     */
    public function getFields($alias = null)
    {
        $fields = array();
        $alias  = is_null($alias) ? '' : $alias.".";

        foreach ($this->field_definitions as $name => $type)
        {
            $fields[$name] = sprintf("%s\"%s\"", $alias, $name);
        }

        return $fields;
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

        return join(', ', array_map(function($name, $table_alias) { return sprintf("%s AS \"%s\"", $name, $table_alias); }, $fields, array_keys($fields)));
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
     * getRemoteSelectFields
     *
     * Return the select fields aliased as table{%s} for use with 
     * createFromForeign filter.
     *
     * @param String $alias Optional alias prefix.
     * @return Array $fields
     */
    public function getRemoteSelectFields($alias = null)
    {
        $fields = array();
        foreach ($this->getSelectFields($alias) as $field_alias => $field_name)
        {
            $fields[sprintf("%s{%s}", $this->getTableName(), $field_alias)] = $field_name;
        }

        return $fields;
    }

    /**
     * createFromForeign
     *
     * This method is intended to be used as a Collection filter.
     * Hydrate an object from the values with keys formatted like table{field}
     * and set it in the values with the table name as key. All the values used 
     * to hydrate the object are removed from the array.
     *
     * @param Array $old_values
     * @return Array 
     */
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
     * convertToPg 
     *
     * Convert values to Postgresql.
     *
     * @param Array $values Values to convert.
     * @return Array Converted values to PG format.
     */
    public function convertToPg(Array $values)
    {
        $out_values = array();
        foreach ($this->field_definitions as $field_name => $pg_type)
        {
            if (!array_key_exists($field_name, $values))
            {
                continue;
            }

            if (is_null($values[$field_name]))
            {
                $out_values[$field_name] = 'NULL';
                continue;
            }

            if (preg_match('/([a-z0-9_\.-]+)(\[\])?/i', $pg_type, $matchs))
            {
                if (count($matchs) > 2)
                {
                    $converter = $this->connection
                    ->getDatabase()
                    ->getConverterFor('Array')
                    ;
                }
                else
                {
                    $converter = $this->connection
                    ->getDatabase()
                    ->getConverterForType($pg_type)
                    ;
                }

                $out_values[$field_name] = $converter
                    ->toPg($values[$field_name], $matchs[1]);
            }
            else
            {
                throw new Exception(sprintf('Error, bad type expression "%s".', $pg_type));
            }
        }

        return $out_values;
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
        $out_values = array();
        foreach ($values as $name => $value)
        {
            if (is_null($value) or $value === '')
            {
                $out_values[$name] = null;
                continue;
            }

            $pg_type = array_key_exists($name, $this->field_definitions) ? $this->field_definitions[$name] : null;

            if (is_null($pg_type))
            {
                $pg_type = array_key_exists($name, $this->virtual_fields) ? $this->virtual_fields[$name] : null;
                if (is_null($pg_type))
                {
                    $out_values[$name] = $value;
                    continue;
                }
            }

            if (preg_match('/([a-z0-9_\.-]+)(\[\])?/i', $pg_type, $matchs))
            {
                if (count($matchs) > 2)
                {
                    $converter = $this->connection
                    ->getDatabase()
                    ->getConverterFor('Array')
                    ;
                }
                else
                {
                    $converter = $this->connection
                    ->getDatabase()
                    ->getConverterForType($pg_type)
                    ;
                }

                $out_values[$name] = $converter
                    ->fromPg($values[$name], $matchs[1]);
            }
            else
            {
                throw new Exception(sprintf('Error, bad type expression "%s".', $pg_type));
            }
        }

        return $out_values;
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
            $where->andWhere(sprintf('%s = ?', $key), array($value));
        }

        return $where;
    }

    /**
     * createCollectionFromStatement
     *
     * Creates a \Pomm\Object\Collection instance from a PDOStatement.
     * 
     * @access protected
     * @param \PDOStatement $stmt 
     * @return \Pomm\Object\Collection
     */
    protected function createCollectionFromStatement(\PDOStatement $stmt)
    {
        return new Collection($stmt, $this);
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
    protected function parseForUpdate($object)
    {
        $tmp = array();

        foreach ($this->convertToPg($object->getFields()) as $field_name => $field_value)
        {
            if (array_key_exists($field_name, array_flip($this->getPrimaryKey()))) continue;
            $tmp[] = sprintf('"%s"=%s', $field_name, $field_value);
        }

        return implode(',', $tmp);
    }

}
