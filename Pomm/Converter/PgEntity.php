<?php
namespace Pomm\Converter;

use Pomm\Converter\ConverterInterface;
use Pomm\Exception\Exception;
use Pomm\Connection\Database;
/**
 * Pomm\Converter\PgEntity - Entity converter
 * 
 * @package Pomm
 * @version $id$
 * @copyright 2011 Grégoire HUBERT 
 * @author Grégoire HUBERT <hubert.greg@gmail.com>
 * @license X11 {@link http://opensource.org/licenses/mit-license.php}
 */
class PgEntity implements ConverterInterface
{
    protected $database;
    protected $class_name;

    public function __construct(Database $database, $class_name)
    {
        $this->database = $database;
        $this->class_name = $class_name;
    }

    public function toPg($data, $type = null)
    {
        if (!is_object($data))
        {
            throw new Exception(sprintf("'%s' converter toPG() method expects argument to be a '%s' instance ('%s given).", get_class($this), $this->class_name, gettype($data)));
        }

        if (! $data instanceof $this->class_name)
        {
            throw new Exception(sprintf("'%s' converter toPG() method expects argument to be a '%s' instance ('%s given).", get_class($this), $this->class_name, get_class($data)));
        }

        if (! $data instanceof \Pomm\Object\BaseObject)
        {
            throw new Exception(sprintf("'%s' converter needs '%s' to be children of Pomm\\Object\\BaseObject.", get_class($this), get_class($data)));
        }

        $fields = array();

        $map = $this->database->createConnection()
            ->getMapFor($this->class_name);

        foreach ($map->getFieldDefinitions() as $field_name => $pg_type)
        {
            if (preg_match('/([a-z0-9_-]+)(\[\])?/i', $pg_type, $matchs))
            {
                if (count($matchs) <= 2)
                {
                    $fields[] = $this->database->getConverterForType($pg_type)->toPg($data->get($field_name));
                }
                else
                {
                    $converter = $this->database->getConverterForType($matchs[1]);
                    $fields[] = sprintf('ARRAY[%s]', join(',', array_map(function($val) use ($converter) {
                        return $converter->toPg($val);
                    }, $data->get($field_name))));
                }
            }
            else
            {
                throw new Exception(sprintf('Error, bad type expression "%s".', $pg_type));
            }

        }

        return sprintf("ROW(%s)::%s", join(',', $fields), $map->getTableName());
    }

    public function fromPg($data, $type = null)
    {
        $values = array();
        $data = trim($data, "()");
        $map = $this->database->createConnection()
            ->getMapFor($this->class_name);

        $elts = preg_split('/[,\s]*"((?:[^\\\\"]|\\\\.)+)"[,\s]*|[,\s]+/', $data, 0, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);

        foreach($map->getFieldDefinitions() as $field => $pg_type)
        {
            if (preg_match('/([a-z0-9_-]+)(\[\])?/i', $pg_type, $matchs))
            {
                if (count($matchs) <= 2)
                {
                    $values[$field] = $this->database->getConverterForType($pg_type)->fromPg(array_shift($elts), $pg_type);
                }
                else
                {
                    $converter = $this->connection->getDatabase()->getConverterForType($pg_type);
                    $values[$field] = array_map(function($val) use ($converter) {
                                return $converter->fromPg($val, $pg_type);
                            },
                            preg_split('/[,\s]*"((?:[^\\\\"]|\\\\.)+)"[,\s]*|[,\s]+/', trim(array_shift($elts), "{}"), 0, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE));
                }
            }
            else
            {
                throw new Exception(sprintf('Error, bad type expression "%s".', $pg_type));
            }
        }

        $object = $map->createObject($values);

        return $object;
    }
}
