<?php

namespace Pomm\Connection;

use Pomm\Exception\Exception;
use Pomm\Tools\ParameterHolder;
class Connection
{
    protected $handler;
    protected $parameter_holder;

    /**
     * __construct()
     * 
     * open a connection to the specified database
     * @access public
     * @param ParameterHolder $parameter_holder the db parameters
     **/
    public function __construct(ParameterHolder $parameter_holder)
    {
        $this->parameter_holder = $parameter_holder;
    }

    protected function launch()
    {
        $connect_string = sprintf('%s:dbname=%s user=%s', 
            $this->parameter_holder['adapter'],
            $this->parameter_holder['database'],
            $this->parameter_holder['user'] 
        );

        $connect_string .= $this->parameter_holder['host'] !== '' ? sprintf(' host=%s', $this->parameter_holder['host']) : '';
        $connect_string .= $this->parameter_holder['port'] !== '' ? sprintf(' port=%d', $this->parameter_holder['port']) : '';
        $connect_string .= $this->parameter_holder['pass'] !== '' ? sprintf(' password=%s', $this->parameter_holder['pass']) : '';

        try
        {
            $this->handler = new \PDO($connect_string);
        }
        catch (\PDOException $e)
        {
            throw new Exception(sprintf('Error connecting to the database with dsn «%s». Driver said "%s".', $connect_string, $e->getMessage()));
        }
    }

  /*
   * __destruct
   *
   * The destructor
   * @access public
   * @return void
   */
  public function __destruct()
  {
    unset($this->handler);
  }

  /**
   * getPdo 
   * Returns the PDO instance of the associated connection
   * 
   * @access public
   * @return PDO
   */
  public function getPdo()
  {
      if (!isset($this->handler))
      {
          $this->launch();
      }

      return $this->handler;
  }

    /**
     * getMapFor 
     * Returns a Map instance of the given model name
     * 
     * @param string $class 
     * @access public
     * @return PommBaseObjectMap
     */
    public function getMapFor($class)
    {
        $class_name = $class.'Map';
        $object = new $class_name($this);

        return $object;
    }

}
