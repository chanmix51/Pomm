<?php

class PgLookWidgetSelectChoice extends sfWidgetFormChoice
{
  public function __construct($options = array(), $attributes = array())
  {
    $options['choices'] = array();

    parent::__construct($options, $attributes);
  }

  /**
   * configure Available options:
   * * model:    The name of the model class (required)
   * * method:   The name of the model class's method to get records from 
   *             (required)
   * * getter    The name of the model's getter to get value from 
   *             (__toString)
   *             If the method does not return a collection, results will be 
   *             sent as is and the getter option will have no effect. If you 
   *             do that, ensure the method returns an associative array 
   *             "id" => "value".
   *
   * @param array $options 
   * @param array $attributes 
   * @access public
   * @return void
   */
  public function configure($options = array(), $attributes = array())
  {
    $this->addRequiredOption('model');
    $this->addRequiredOption('method');
    $this->addOption('getter', '__toString');

    parent::configure($options, $attributes);
  }

  /**
   * getChoices 
   * Return an associative array containing key => value of the select box
   * 
   * @access public
   * @return array
   */
  public function getChoices()
  {
    $results = call_user_func(array(PgLook::getMapFor($this->getOption('model')), $this->getOption('method')));

    if (! $results instanceof PgLookCollection)
    {
      return $results;
    }

    $choices = array();
    foreach ($results as $result)
    {
      $choices[join('.', $result->getPrimaryKey())] = call_user_func(array($result, $this->getOption('getter')));
    }

    return $choices;
  }
}

