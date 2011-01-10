<?php

/**
 * sfPgLookValidatorChoice 
 * 
 * @uses sfValidatorBase
 * @package sfPgLookPlugin
 * @version $id$
 * @copyright 2010 Grégoire HUBERT 
 * @author Grégoire HUBERT <hubert.greg@gmail.com>
 * @license MIT/X11 {@link http://opensource.org/licenses/mit-license.php}
 */
class sfPgLookValidatorChoice extends sfValidatorBase
{
  /**
   * configure 
   * Configure the widget. Following options are accepted :
   * model : the name of the model class to use
   * method : The method's name to use to retreive the list
   * column : the name of the field 
   * 
   * @param array $options 
   * @param array $errors 
   * @access public
   * @return void
   */
  public function configure($options = array(), $errors = array())
  {
    $this->addRequiredOption('model');
    $this->addRequiredOption('method');
    $this->addoption('column');
  }

  /**
   * doClean 
   * @see sfValidatorBase
   * 
   * @param mixed $value 
   * @access protected
   * @return mixed
   */
  protected function doClean($value)
  {
    $map_class = PgLook::getMapFor($this->getOption('model'));

    $method = $this->getOption('method');
    if (!method_exists($map_class, $method))
    {
      throw new PgLookException(sprintf('Class "%s" does not have a "%s" method.', get_class($map_class), $method));
    }

    $column = $this->getOption('column');
    if (!$map_class->hasField($column))
    {
      throw new PgLookException(sprintf('Table "%s" has no such column "%s".', $map_class->getTableName(), $column));
    }

    $result = call_user_func(array($map_class, $method), array($column => $value));

    if (($result instanceof PgLookCollection and $result->isEmpty())
      or
       (!$result instanceOf PgLookBaseObject))
    {
      throw new sfValidatorError($this, 'invalid', array('value' => $value));
    }

    return $value;
  }
}

