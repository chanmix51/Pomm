<?php

namespace Pomm\Object;

use \Pomm\Exception\Exception;

/**
 * SimpleCollection 
 * 
 * @package Pomm
 * @version $id$
 * @copyright 2011 Grégoire HUBERT 
 * @author Grégoire HUBERT <hubert.greg@gmail.com>
 * @license MIT/X11 {@link http://opensource.org/licenses/mit-license.php}
 */
class SimpleCollection implements \Iterator, \Countable 
{
    protected $stmt;
    protected $object_map;
    protected $position = 0;

    /**
     * __construct 
     * 
     * @param \PDOStatement              $stmt
     * @param \Pomm\Object\BaseObjectMap $object_map
     */
    public function __construct(\PDOStatement $stmt, \Pomm\Object\BaseObjectMap $object_map)
    {
        $this->stmt = $stmt;
        $this->object_map = $object_map;
        $this->position = $this->stmt === false ? null : 0;
    }

    /**
     * __destruct
     *
     * Closes the cursor when the collection is cleared.
     **/
    public function __destruct()
    {
        $this->stmt->closeCursor();
    }

    /**
     * get
     *
     * Return a particular result.
     *
     * @param Integer $index
     * @return \Pomm\Object\BaseObject
     **/

    public function get($index)
    {
        $values = $this->stmt->fetch(\PDO::FETCH_ASSOC, \PDO::FETCH_ORI_ABS, $index);

        if ($values === false) 
            return false;

        return $this->object_map->createObjectFromPg($values);
    }

    /**
     * has
     *
     * Return true if the given index exists false otherwise.
     *
     * @param Intger $index
     * @return Boolean
     **/
    public function has($index)
    {
        return $index < $this->count();
    }

    /**
     * count 
     * 
     * @see \Countable
     * @return Integer
     */
    public function count()
    {
        return $this->stmt->rowCount();
    }

    /**
     * rewind 
     * 
     * @see \Iterator
     */
    public function rewind() 
    {
        if ($this->position !== 0)
        {
            throw new Exception(sprintf("Can not rewind a cursor. Use the \\Pomm\\Object\\Collection instead."));
        }
    }

    /**
     * current 
     * 
     * @see \Iterator
     */
    public function current() 
    {
        return $this->get($this->position);
    }

    /**
     * key 
     * 
     * @see \Iterator
     */
    public function key() 
    {
        return $this->position;
    }

    /**
     * next 
     * 
     * @see \Iterator
     */
    public function next() 
    {
        ++$this->position;
    }

    /**
     * valid 
     * 
     * @see \Iterator
     * @return Boolean
     */
    public function valid() 
    {
        return $this->has($this->position);
    }

    /**
     * isFirst 
     * Is the iterator on the first element ?
     *
     * @return Boolean
     */
    public function isFirst()
    {
        return $this->position === 0;
    }

    /**
     * isLast 
     *
     * Is the iterator on the last element ?
     * 
     * @return Boolean
     */
    public function isLast()
    {
        return $this->position === $this->count() - 1;
    }

    /**
     * isEmpty 
     *
     * Is the collection empty (no element) ?
     * 
     * @return Boolean
     */
    public function isEmpty()
    {
        return $this->stmt->rowCount() === 0;
    }

    /**
     * isEven 
     *
     * Is the iterator on an even position ?
     * 
     * @return Boolean
     */
    public function isEven()
    {
        return ($this->position % 2) === 0;
    }

    /**
     * isOdd 
     *
     * Is the iterator on an odd position ?
     * 
     * @return Boolean
     */
    public function isOdd()
    {
        return ($this->position % 2) === 1;
    }

    /**
     * getOddEven 
     *
     * Return 'odd' or 'even' depending on the element index position.
     * Useful to style list elements when printing lists to do 
     * <li class="line_<?php $list->getOddEven() ?>">.
     * 
     * @return String
     */
    public function getOddEven()
    {
        return $this->position % 2 ? 'odd' : 'even';
    }

    /**
     * extract
     *
     * Return an array of results. Useful if you want to serialize a result or 
     * export it as a JSON format. Filters are still executed on all the 
     * fetched results.
     *
     * @param String $name The name of the resultset (Defaults to entity FQCN)
     * @return Array
     **/
    public function extract($name = null)
    {
        $name = is_null($name) ? $this->object_map->getObjectClass() : $name;
        $results = array();

        foreach ($this as $result)
        {
            $results[] = $result->extract();
        }

        return array($name => $results);
    }
}
