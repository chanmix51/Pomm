<?php
Namespace Pomm;

/**
 * PommCollection 
 * 
 * @uses ArrayAccess
 * @uses Iterator
 * @uses Countable
 * @package PommBundle
 * @version $id$
 * @copyright 2011 Grégoire HUBERT 
 * @author Grégoire HUBERT <hubert.greg@gmail.com>
 * @license MIT/X11 {@link http://opensource.org/licenses/mit-license.php}
 */
class PommCollection implements ArrayAccess, Iterator, Countable 
{
  protected $collection = array();
  protected $position;

  /**
   * __construct 
   * 
   * @param Array $data 
   * @access public
   * @return void
   */
  public function __construct(Array $data)
  {
    $this->collection = $data;
    $this->position = $this->count() > 0 ? 0 : null;
  }

  /**
   * count 
   * 
   * @access public
   * @return integer
   */
  public function count()
  {
    return count($this->collection);
  }

  /**
   * addData 
   * 
   * @param mixed $data 
   * @access public
   * @return void
   */
  public function addData($data)
  {
    $this->collection[] = $data;
  }

  /**
   * offsetSet 
   * 
   * @param mixed $offset 
   * @param mixed $value 
   * @access public
   * @return void
   */
  public function offsetSet($offset, $value) 
  {
    $this->collection[$offset] = $value;
  }

  /**
   * offsetExists 
   * 
   * @param mixed $offset 
   * @access public
   * @return boolean
   */
  public function offsetExists($offset) 
  {
    return isset($this->collection[$offset]);
  }

  /**
   * offsetUnset 
   * 
   * @param mixed $offset 
   * @access public
   * @return void
   */
  public function offsetUnset($offset) 
  {
    unset($this->collection[$offset]);
  }

  /**
   * offsetGet 
   * 
   * @param mixed $offset 
   * @access public
   * @return mixed
   */
  public function offsetGet($offset) 
  {
    return isset($this->collection[$offset]) ? $this->collection[$offset] : null;
  }

  /**
   * rewind 
   * 
   * @access public
   * @return void
   */
  public function rewind() 
  {
    $this->position = 0;
  }

  /**
   * current 
   * 
   * @access public
   * @return void
   */
  public function current() 
  {
    return $this->collection[$this->position];
  }

  /**
   * key 
   * 
   * @access public
   * @return void
   */
  public function key() 
  {
    return $this->position;
  }

  /**
   * next 
   * 
   * @access public
   * @return void
   */
  public function next() 
  {
    ++$this->position;
  }

  /**
   * valid 
   * 
   * @access public
   * @return boolean
   */
  public function valid() 
  {
    return isset($this->collection[$this->position]);
  }

  /**
   * isFirst 
   * Is the iterator on the first element ?
   *
   * @access public
   * @return boolean
   */
  public function isFirst()
  {
    return $this->position == 0;
  }

  /**
   * isLast 
   * Is the iterator on the last element ?
   * 
   * @access public
   * @return boolean
   */
  public function isLast()
  {
    return $this->position == $this->count() - 1;
  }

  /**
   * isEmpty 
   * Is the collection empty (no element) ?
   * 
   * @access public
   * @return boolean
   */
  public function isEmpty()
  {
    return is_null($this->position);
  }

  /**
   * isEven 
   * Is the iterator on an even position ?
   * 
   * @access public
   * @return boolean
   */
  public function isEven()
  {
    return ($this->position % 2) == 0;
  }

  /**
   * isOdd 
   * 
   * @access public
   * @return boolean
   */
  public function isOdd()
  {
    return ($this->position % 2) == 1;
  }

  /**
   * getOddEven 
   * Return 'odd' or 'even' depending on the element index position
   * Useful to style list elements when printing lists to do 
   * <li class="line_<?php $list->getOddEven() ?>">
   * 
   * @access public
   * @return string
   */
  public function getOddEven()
  {
    return $this->position % 2 ? 'odd' : 'even';
  }
}
