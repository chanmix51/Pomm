<?php

namespace Pomm\Converter;

use \Pomm\Converter\ConverterInterface;
use \Pomm\Object\RowStructure;
use \Pomm\Connection\Database;
use \Pomm\Exception\Exception as PommException;

/**
 * Pomm\Converter\PgRow - Row converter
 *
 * @package Pomm
 * @version $id$
 * @copyright 2011 - 2013 Grégoire HUBERT
 * @author Grégoire HUBERT <hubert.greg@gmail.com>
 * @license X11 {@link http://opensource.org/licenses/mit-license.php}
 */
class PgRow implements ConverterInterface
{
    protected $database;
    protected $row_structure;
    protected $virtual_fields;
    protected $class_name;

    /**
     * __construct
     *
     * @param Database $database
     * @param RowStructure $structure
     * @param String class_name
     */
    public function __construct(Database $database, RowStructure $structure, $class_name = null)
    {
        $this->database = $database;
        $this->row_structure = $structure;
        $this->class_name = $class_name;
    }

    /**
     * setVirtualFields
     *
     * This allows to add extra fields and types to the row fixed structure.
     *
     * @param Array $virtual_fields
     */
    public function setVirtualFields(Array $virtual_fields)
    {
        $this->virtual_fields = $virtual_fields;
    }

    /**
     * convertToPg
     *
     * Call the according converters on a set of values.
     * This method is used internally by BaseObjectMap class.
     *
     * @param Array $values
     * @return Array $values converter values
     */
    public function convertToPg(Array $values)
    {
        $out_values = array();

        foreach ($this->row_structure->getDefinition() as $field_name => $pg_type) {
            if (!array_key_exists($field_name, $values)) {
                continue;
            }

            if (is_null($values[$field_name])) {
                $out_values[$field_name] = 'NULL';
                continue;
            }

            if ($values[$field_name] instanceof \Pomm\Type\RawString) {
                $out_values[$field_name] = (string) $values[$field_name];
                continue;
            }

            if (preg_match('/([a-z0-9_\.-]+)(\[\])?/i', $pg_type, $matchs)) {
                if (count($matchs) > 2) {
                    $converter = $this->database->getConverterFor('Array');
                } else {
                    $converter = $this->database->getConverterForType($pg_type);
                }

                $out_values[$field_name] = $converter
                    ->toPg($values[$field_name], $matchs[1]);
            } else {
                throw new PommException(sprintf('Error, bad type expression "%s".', $pg_type));
            }
        }

        return $out_values;
    }

    /**
     * @see ConverterInterface
     */
    public function toPg($data, $type = null)
    {
        if ($this->class_name != null) {
            if (! $data instanceof $this->class_name) {
                throw new PommException(sprintf("This converter deals with '%s' instances ('%' given).", $this->class_name, get_type($data)));
            }

            $data = (Array) $data;
        }

        return sprintf("ROW(%s)%s", join(',', $this->convertToPg($data)), is_null($type) ? '' : sprintf('::%s', $type));
    }

    /**
     * convertFromPg
     *
     * call the fromPg conveters on a set of values.
     * This method is used internally by BaseObjectMap class.
     *
     * @param Array $values
     * @return Array $values converter values
     */
    public function convertFromPg(Array $values)
    {
        $out_values = array();
        foreach ($values as $name => $value) {
            if (is_null($value)) {
                $out_values[$name] = null;
                continue;
            }

            $pg_type = $this->row_structure->hasField($name) ? $this->row_structure->getTypeFor($name) : null;

            if (is_null($pg_type)) {
                $pg_type = array_key_exists($name, $this->virtual_fields) ? $this->virtual_fields[$name] : null;

                if (is_null($pg_type)) {
                    $out_values[$name] = $value;
                    continue;
                }
            }

            if (preg_match('/([a-z0-9_\.-]+)(\[\])?/i', $pg_type, $matchs)) {
                if (count($matchs) > 2) {
                    $converter = $this->database->getConverterFor('Array');
                } else {
                    $converter = $this->database->getConverterForType($pg_type);
                }

                $out_values[$name] = $converter
                    ->fromPg($values[$name], $matchs[1]);
            } else {
                throw new PommException(sprintf('Error, bad type expression "%s".', $pg_type));
            }
        }

        return $out_values;
    }

    /**
     * @see ConverterInterface
     */
    public function fromPg($data, $type = null)
    {
        $elts = str_getcsv(trim($data, '()'));

        $values = array();
        foreach ($this->row_structure->getFieldNames() as $field_name) {
            $values[$field_name] = stripcslashes(array_shift($elts));
        }

        if (count($elts) > 0) {
            $values['_extra'] = $elts;
        }

        $values = $this->convertFromPg($values);

        if ($this->class_name != null) {
            $class = $this->class_name;

            return new $class($values);
        }

        return $values;
    }
}
