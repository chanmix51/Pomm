<?php
namespace Pomm\Object;
use Pomm\Exception\Exception;
use Pomm\Exception\SqlException;

use Pomm\Type\ArrayType;
use Pomm\Type\BaseType;
use Pomm\Type\BoolType;
use Pomm\Type\HStoreType;
use Pomm\Type\IntType;
use Pomm\Type\LTreeType;
use Pomm\Type\StrType;
use Pomm\Type\TimestampType;

/**
 * BaseObjectMap 
 * 
 * @abstract
 * @package PommBundle
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
   * - connection the database name to use to query on this model objects
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
  public function createObject()
  {
    $reflection = new \ReflectionClass(get_class($this));
    $class_name = sprintf("%s\\%s", $reflection->getNamespaceName(), $this->object_class);
    return new $class_name($this->pk_fields, $this->field_definitions);
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
   * The constructor. Most of the time, you should use Pomm::getMapFor($class_name) to chain calls
   * 
   * @access public
   * @return void
   */
  public function __construct()
  {
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
    return $this->connection->getPdo()->prepare($sql);
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
        $type = PDO::PARAM_INT;
      }
      elseif (is_bool($value))
      {
        $type = PDO::PARAM_BOOL;
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
    return $this->createObjectsFromStmt($this->doQuery($sql, $values));
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
  protected function createObjectsFromStmt(PDOStatement $stmt)
  {
    $objects = array();
    foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $values)
    {
      $object = $this->createObject();
      $object->hydrate($this->convertPg($values, 'fromPg'));
      $object->_setStatus(BaseObject::EXIST);

      $objects[] = $object;
    }

    return new Collection($objects);
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
    return $this->query(sprintf('SELECT * FROM %s;', $this->object_name), array());
  }

  /**
   * findWhere 
   * 
   * @param string $where 
   * @param array $values 
   * @access public
   * @return Collection
   */
  public function findWhere($where, $values)
  {
    return $this->query(sprintf('SELECT * FROM %s WHERE %s;', $this->object_name, $where), $values);
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

    $result = $this->findWhere($this->createSqlAndFrom($values), array_values($values));

    return count($result) == 1 ? $result[0] : null;
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
      $converter = array_key_exists($name, $this->field_definitions) ? $this->field_definitions[$name] : null;
      if (is_null($converter))
      {
        $out_values[$name] = $value;
        continue;
      }
      if (is_null($value)) continue;

      if (!preg_match('/([a-z]+)(?:\[([a-z]+)\])?/i', $converter, $matchs))
      {
        throw new Exception(sprintf('Error, bad type converter expression "%s".', $converter));
      }
      $type = $matchs[1];
      $subtype = count($matchs) > 2 ? $matchs[2] : '';

      if ($subtype !== '')
      {
        call_user_func(array($type, 'setSubType'), $subtype);
      }

      $out_values[$name] = call_user_func(array($type, $method), $value);
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
      throw new Exception($message);
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
    $sql = sprintf('DELETE FROM %s WHERE %s', $this->object_name, $this->createSqlAndFrom($pk));
    return $this->query($sql, array_values($pk));
  }

  /**
   * saveOne 
   * Save an instance. Use this to insert or update an object
   *
   * @param BaseObject $object 
   * @access public
   * @return Collection
   */
  public function saveOne(BaseObject &$object)
  {
    $this->checkObject($object, sprintf('"%s" class does not know how to save "%s" objects.', get_class($this), get_class($object)));

    if ($object->_getStatus() & BaseObject::EXIST)
    {
      $sql = sprintf('UPDATE %s SET %s WHERE %s', $this->object_name, $this->parseForUpdate($object), $this->createSqlAndFrom($object->getPrimaryKey()));

      $this->beginTransaction()->query($sql, array_values($object->getPrimaryKey()));
      $object = $this->findByPk($object->getPrimaryKey());
      $this->commitTransaction();
      $object->_setStatus(BaseObject::EXIST);
    }
    else
    {
      $pg_values = $this->parseForInsert($object);
      $sql = sprintf('INSERT INTO %s (%s) VALUES (%s) RETURNING *;', $this->object_name, join(',', array_keys($pg_values)), join(',', array_values($pg_values)));

      $collection = $this->query($sql, array());
      $object = $collection[0];
      $object->_setStatus(BaseObject::EXIST);
    }
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
      if (array_key_exists($field_name, $object->getPrimaryKey())) continue;
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
      if (array_key_exists($field_name, $object->getPrimaryKey())) continue;
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
   * @return string
   */
  public function getTableName()
  {
    return $this->object_name;
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
    $this->deleteByPk($object->getPrimaryKey());
    $object->_setStatus(BaseObject::NONE);
  }

  /**
   * beginTransaction 
   * 
   * @access public
   * @return void
   */
  public function beginTransaction()
  {
    if (!$this->connection->getPdo()->beginTransaction())
    {
      throw new Exception(sprintf('Error while trying to start a transaction. SQL said "%s".', $this->connection->getPdo()->errorInfo()));
    }

    return $this;
  }

  /**
   * commitTransaction 
   * 
   * @access public
   * @return void
   */
  public function commitTransaction()
  {
    if (!$this->connection->getPdo()->commit())
    {
      throw new Exception(sprintf('Error while trying to commit a transaction. SQL said "%s".', $this->connection->getPdo()->errorInfo()));
    }

    return $this;
  }

  /**
   * rollbackTransaction 
   * 
   * @access public
   * @return void
   */
  public function rollbackTransaction()
  {
    if (!$this->connection->getPdo()->rollback())
    {
      throw new Exception(sprintf('Error while trying to rollback a transaction. SQL said "%s".', $this->connection->getPdo()->errorInfo()));
    }

    return $this;
  }

  public function findPommWhere(Where $where)
  {
    return $this->findWhere($where, $where->getValues());
  }
}
