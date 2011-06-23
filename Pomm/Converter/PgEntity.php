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

    public function toPg($data)
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

        foreach ($map->getFieldDefinitions() as $field_name => $converter_name)
        {
            $fields[] = $this->database->getConverterFor($converter_name)->toPg($data->get($field_name));
        }

        $serial = preg_replace('/\'/', '"', join(', ', $fields));

        return sprintf("'(%s)'::%s", $serial, $map->getTableName());
    }

    public function fromPg($data)
    {
        $values = array();
        $data = trim($data, "()");
        $map = $this->database->createConnection()
            ->getMapFor($this->class_name);

        $elts = preg_split('/[,\s]*"([^"]+)"[,\s]*|[,\s]+/', $data, 0, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);

        foreach($map->getFieldDefinitions() as $field => $converter_name)
        {
            $values[$field] = $this->database->getConverterFor($converter_name)->fromPg(array_shift($elts));
        }

        $object = $map->createObject();
        $object->hydrate($values);

        return $object;
    }
}
