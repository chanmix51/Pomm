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

    /**
     * constructor
     *
     * @param Pomm\Connection\Database  $database
     * @param String                    $class_name  Model class to get the Map from.
     **/
    public function __construct(Database $database, $class_name)
    {
        $this->database = $database;
        $this->class_name = $class_name;
    }

    /**
     * @see Pomm\Converter\ConverterInterface
     **/
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

        $map = $this->database->createConnection()
            ->getMapFor($this->class_name);

        return sprintf("ROW(%s)%s", join(',', $map->convertToPg($data->extract())), is_null($type) ? '' : sprintf('::%s', $type));
    }

    /**
     * @see Pomm\Converter\ConverterInterface
     **/
    public function fromPg($data, $type = null)
    {
        $map = $this->database->createConnection()
            ->getMapFor($this->class_name);

        $elts = preg_split('/[,\s]*"((?:[^\\\\"]|\\\\.|"")+)"[,\s]*|[,\s]+/', trim($data, "()"), 0, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);

        $fields = array();
        foreach ($map->getFieldDefinitions() as $field_name => $pg_type)
        {
            $fields[$field_name] = array_shift($elts);
        }

        if (count($elts) > 0)
        {
            $fields['_extra'] = $elts;
        }

        $object = $map->createObject($map->convertFromPg($fields));

        return $object;
    }
}
