<?php
namespace Pomm\Object;

use Pomm\Exception\Exception;

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
    protected $filters = array();

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

    /**
     * __destruct
     * The instance destructor
     * It closes the cursos when the collection is cleared.
     *
     * @access public
     **/
    public function __destruct()
    {
        $this->stmt->closeCursor();
    }

    /**
     * registerFilter
     * Register a callable as filter
     *
     * @access public
     * @param Callable $callable
     **/

    public function registerFilter($callable)
    {
        $this->filters[] = $callable;
    }

    /**
     * unregisterFilter
     * unregister a filter
     *
     * @access public
     * @param Callable the callable to unregister
     **/
    public function unregisterFilter($callable)
    {
        $this->filters = array_map(function($value) use ($callable) {
            return $value == $callable ? $value : null;
        }, $this->filters);
    }

    /**
     * resetFilters
     * remove all filters
     *
     * @access public
     **/
    public function resetFilters()
    {
        $this->filters = array();
    }

    /**
     * get
     * Return a particular result
     *
     * @param index
     * @access public
     * @return \Pomm\Object\BaseObject
     **/

    public function get($index)
    {
        $object = $this->object_map->createObject();
        $values = $this->stmt->fetch(\PDO::FETCH_ASSOC, \PDO::FETCH_ORI_ABS, $index);
        $values = $this->object_map->convertPg($values, 'fromPg');

        foreach($this->filters as $index => $filter)
        {
            $values = $filter($values);
            if (!is_array($values))
            {
                throw new Exception(sprintf("Filters have to return an Array. Filter number %d returned a '%s'.", $index, gettype($values)));
            }
        }

        $object->hydrate($values);
        $object->_setStatus(BaseObject::EXIST);

        return $object;
    }

    /**
     * has
     * Return true if the given index exists false otherwise
     *
     * @access public
     * @param index
     * @return Boolean
     **/

    public function has($index)
    {
        return $index < $this->count();
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
        return $this->get($this->position);
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
        return $this->has($this->position);
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
