<?php

namespace Pomm\Converter;

use \Pomm\Converter\ConverterInterface;
use \Pomm\Exception\Exception;
use \Pomm\Object\BaseObjectMap;

/**
 * Pomm\Converter\PgEntity - Entity converter
 *
 * @package Pomm
 * @version $id$
 * @copyright 2011 - 2013 Grégoire HUBERT
 * @author Grégoire HUBERT <hubert.greg@gmail.com>
 * @license X11 {@link http://opensource.org/licenses/mit-license.php}
 */
class PgEntity implements ConverterInterface
{
    protected $map;

    /**
     * constructor
     *
     * @param Pomm\Object\BaseObjectMap  $map
     */
    public function __construct(BaseObjectMap $map)
    {
        $this->map = $map;
    }

    /**
     * @see Pomm\Converter\ConverterInterface
     */
    public function toPg($data, $type = null)
    {
        $class_name = $this->map->getObjectClass();

        if (!is_object($data))
        {
            throw new Exception(sprintf("'%s' converter toPG() method expects argument to be a '%s' instance ('%s' given).", get_class($this), $class_name, gettype($data)));
        }

        if (! $data instanceof $class_name)
        {
            throw new Exception(sprintf("'%s' converter toPG() method expects argument to be a '%s' instance ('%s' given).", get_class($this), $class_name, get_class($data)));
        }

        if (! $data instanceof \Pomm\Object\BaseObject)
        {
            throw new Exception(sprintf("'%s' converter needs '%s' to be children of Pomm\\Object\\BaseObject.", get_class($this), get_class($data)));
        }

        $fields = array();

        foreach ($this->map->getRowStructure()->getFieldNames() as $field_name)
        {
            $fields[$field_name] = $data->has($field_name) ? $data[$field_name] : null;
        }

        return $this->map->getConverter()->toPg($fields);
    }

    /**
     * @see Pomm\Converter\ConverterInterface
     */
    public function fromPg($data, $type = null)
    {
        return $this->map->makeObjectFromPg($this->map->getConverter()->fromPg($data, $type));
    }
}
