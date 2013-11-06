<?php

namespace Pomm\Converter;

use \Pomm\Converter\ConverterInterface;
use \Pomm\Object\RowStructure;
use \Pomm\Connection\Database;

class PgRow implements ConverterInterface
{
    protected $database;
    protected $row_structure;
    protected $virtual_fields;

    public function __construct(Database $database, RowStructure $structure)
    {
        $this->database = $database;
        $this->row_structure = $structure;
    }

    public function setVirtualFields(Array $virtual_fields)
    {
        $this->virtual_fields = $virtual_fields;
    }

    public function convertToPg($values)
    {
        $out_values = array();

        foreach ($this->row_structure->getDefinition() as $field_name => $pg_type)
        {
            if (!array_key_exists($field_name, $values))
            {
                continue;
            }

            if (is_null($values[$field_name]))
            {
                $out_values[$field_name] = 'NULL';
                continue;
            }

            if ($values[$field_name] instanceOf \Pomm\Type\RawString)
            {
                $out_values[$field_name] = (string) $values[$field_name];
                continue;
            }

            if (preg_match('/([a-z0-9_\.-]+)(\[\])?/i', $pg_type, $matchs))
            {
                if (count($matchs) > 2)
                {
                    $converter = $this->database->getConverterFor('Array');
                }
                else
                {
                    $converter = $this->database->getConverterForType($pg_type);
                }

                $out_values[$field_name] = $converter
                    ->toPg($values[$field_name], $matchs[1]);
            }
            else
            {
                throw new Exception(sprintf('Error, bad type expression "%s".', $pg_type));
            }
        }

        return $out_values;
    }

    public function toPg($values, $type = null)
    {
        return sprintf("ROW(%s)%s", join(',', $this->convertToPg($values)), is_null($type) ? '' : sprintf('::%s', $type));
    }

    public function convertFromPg(Array $values)
    {
        $out_values = array();
        foreach ($values as $name => $value)
        {
            if (is_null($value))
            {
                $out_values[$name] = null;
                continue;
            }

            $pg_type = $this->row_structure->hasField($name) ? $this->row_structure->getTypeFor($name) : null;

            if (is_null($pg_type))
            {
                $pg_type = array_key_exists($name, $this->virtual_fields) ? $this->virtual_fields[$name] : null;

                if (is_null($pg_type))
                {
                    $out_values[$name] = $value;
                    continue;
                }
            }

            if (preg_match('/([a-z0-9_\.-]+)(\[\])?/i', $pg_type, $matchs))
            {
                if (count($matchs) > 2)
                {
                    $converter = $this->database->getConverterFor('Array');
                }
                else
                {
                    $converter = $this->database->getConverterForType($pg_type);
                }

                $out_values[$name] = $converter
                    ->fromPg($values[$name], $matchs[1]);
            }
            else
            {
                throw new Exception(sprintf('Error, bad type expression "%s".', $pg_type));
            }
        }

        return $out_values;
    }

    public function fromPg($data, $type = null)
    {
            $elts = str_getcsv(trim($data, '()'));

            $values = array();
            foreach ($this->row_structure->getFieldNames() as $field_name)
            {
                $values[$field_name] = stripcslashes(array_shift($elts));
            }

            if (count($elts) > 0)
            {
                $values['_extra'] = $elts;
            }

            return $this->convertFromPg($values);
    }
}
