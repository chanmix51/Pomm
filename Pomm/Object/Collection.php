<?php
namespace Pomm\Object;

/**
 * Collection 
 * 
 * @package Pomm
 * @version $id$
 * @copyright 2011 Grégoire HUBERT 
 * @author Grégoire HUBERT <hubert.greg@gmail.com>
 * @license MIT/X11 {@link http://opensource.org/licenses/mit-license.php}
 */
class Collection implements \Iterator, \Countable 
{
    protected $stmt;
    protected $object_map;
    protected $position = 0;

    /**
     * __construct 
     * 
     * @param Array $data 
     * @access public
     * @return void
     */
    public function __construct(\PDOStatement $stmt, \Pomm\Object\BaseObjectMap $object_map)
    {
        $this->stmt = $stmt;
        $this->object_map = $object_map;
        $this->position = $this->stmt === false ? null : 0;
    }

    public function __destruct()
    {
        $this->stmt->closeCursor();
    }

    /**
     * count 
     * 
     * @access public
     * @see \Countable
     * @return integer
     */
    public function count()
    {
        return $this->stmt->rowCount();
    }


    /**
     * rewind 
     * 
     * @access public
     * @see \Iterator
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
     * @see \Iterator
     * @return void
     */
    public function current() 
    {
        $values = $this->stmt->fetch(\PDO::FETCH_ASSOC, \PDO::FETCH_ORI_ABS, $this->position);
        $object = $this->object_map->createObject();

        $object->hydrate($this->object_map->convertPg($values, 'fromPg'));
        $object->_setStatus(BaseObject::EXIST);

        return $object;
    }

    /**
     * key 
     * 
     * @access public
     * @see \Iterator
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
     * @see \Iterator
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
     * @see \Iterator
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
