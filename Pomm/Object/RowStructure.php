<?php

namespace Pomm\Object;

/**
 * RowStructure
 *
 * Represent a composite structure like table or row.
 *
 * @package Pomm
 * @version $id$
 * @copyright 2011-2013 Grégoire HUBERT
 * @author Grégoire HUBERT <hubert.greg@gmail.com>
 * @license MIT/X11 {@link http://opensource.org/licenses/mit-license.php}
 */
class RowStructure
{
    protected $field_definitions;

    /**
     * __construct
     *
     * @param Array field definition
     */
    public function __construct(Array $field_definitions = array())
    {
        $this->field_definitions = $field_definitions;
    }

    /**
     * addField
     *
     * Add a new field structure.
     *
     * @param String $name
     * @param String $type
     */
    public function addField($name, $type)
    {
        $this->field_definitions[$name] = $type;

        return $this;
    }

    /**
     * getFieldNames
     *
     * Return an array of all field names
     *
     * @return Array 
     */
    public function getFieldNames()
    {
        return array_keys($this->field_definitions);
    }

    /**
     * hasField
     *
     * Check if a field exist in the structure
     *
     * @param String $name
     * @return Bool
     */
    public function hasField($name)
    {
        return array_key_exists($name, $this->field_definitions);
    }

    /**
     * getTypeFor
     *
     * Return the type associated with the field
     *
     * @param String $name
     * @return String $type
     */
    public function getTypeFor($name)
    {
        return $this->field_definitions[$name];
    }

    /**
     * getDefinition
     *
     * Return all fields and types
     *
     * @return Array
     */
    public function getDefinition()
    {
        return $this->field_definitions;
    }
}
