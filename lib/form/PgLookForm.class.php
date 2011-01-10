<?php

/**
 * PgLookForm 
 * 
 * @uses PgLookBaseForm
 * @abstract
 * @package sfPgLookPlugin
 * @version $id$
 * @copyright 2010 Grégoire HUBERT 
 * @author Grégoire HUBERT <hubert.greg@gmail.com>
 * @license MIT/X11 {@link http://opensource.org/licenses/mit-license.php}
 */
abstract class PgLookForm extends PgLookBaseForm
{
  protected $object;
  protected $is_new = true;

  /**
   * getClassName 
   * Returns the class name of the form's associated object
   * 
   * @abstract
   * @access protected
   * @return string
   */
  abstract protected function getClassName();

  /**
   * __construct 
   * If an object is passed, default values will be set upon object's attributes
   * 
   * @param PgLookBaseObject $object 
   * @param array $options 
   * @param string $CSRFSecret 
   * @access public
   * @return void
   */
  public function __construct(PgLookBaseObject $object = null, $options = array(), $CSRFSecret = null)
  {
    $class_name = $this->getClassName();
    if (is_null($object))
    {
      $this->object = PgLook::getMapFor($this->getClassName())->createObject();
    }
    elseif (!$object instanceOf $class_name)
    {
      throw new PgLookSqlException(sprintf('"%s" forms can only be fed with a "%s" object ("%s" given).', get_class($this), $class_name, get_class($object)));
    }
    else
    {
      $this->object = $object;
    }

    parent::__construct($this->object->extract(), $options, $CSRFSecret);
  }

  /**
   * bindAndSave 
   * bind, validate and if valid, save the object in the database
   * if the form is valid, returns the saved object
   * if not, return boolean FALSE
   * 
   * @param Array $tainted_values 
   * @param Array $tainted_files 
   * @access public
   * @return mixed
   */
  public function bindAndSave(Array $tainted_values = array(), Array $tainted_files = array())
  {
    $this->bind($tainted_values, $tainted_files);
    if ($this->isValid())
    {
      $this->processValues();
      PgLook::getMapFor($this->getClassName())->saveOne($this->object);

      return $this->object;
    }

    return false;
  }

  /**
   * getObject 
   * get the form's internal object
   * 
   * @access public
   * @return PgLookBaseObject
   */
  public function getObject()
  {
    return $this->object;
  }

  /**
   * processValues 
   * If the form is valid, check if it has methods to process the cleaned
   * values. Methods are in the form "processMyValue($value)". The method's
   * returned value is substitued to the form's value. If NULL is returned, the 
   * value is deleted from form's values.
   * 
   * @access protected
   * @return void
   */
  protected function processValues()
  {
    $values = array();
    foreach($this->getValues() as $field => $value)
    {
      $method = sprintf('process%s', sfInflector::camelize($field));
      if (method_exists($this, $method))
      {
        $ret = call_user_func(array($this, $method), array($value));
        if (!is_null($ret))
        {
          $values[$field] = $ret;
        }
      }
      else
      {
        $values[$field] = $value;
      }
    }

    $this->object->hydrate($values);
    if (!$this->isNew())
    {
      $this->object->setStatus(PgLookBaseObject::EXIST);
    }

  }

  /**
   * isNew 
   * 
   * @access public
   * @return boolean
   */
  public function isNew()
  {
    return $this->is_new;
  }

  /**
   * setNew 
   * 
   * @param boolean $new 
   * @access public
   * @return void
   */
  public function setNew($new)
  {
    $this->is_new = (boolean) $new;
  }

}
