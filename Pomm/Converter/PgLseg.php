<?php
namespace Pomm\Converter;

use Pomm\Converter\ConverterInterface;
use Pomm\Type\Point;
use Pomm\Type\Lseg;
use Pomm\Exception\Exception;

/**
 * Pomm\Converter\PgLseg - Geometric Segment converter
 * 
 * @package Pomm
 * @version $id$
 * @copyright 2011 Grégoire HUBERT 
 * @author Grégoire HUBERT <hubert.greg@gmail.com>
 * @license X11 {@link http://opensource.org/licenses/mit-license.php}
 */
class PgLseg implements ConverterInterface
{
    protected $class_name;
    protected $point_converter;

    /**
     * __construct()
     *
     * @param String            $class_name      Optional fully qualified Segment type class name.
     * @param Pomm\Type\PgPoint $point_converter Point converter to be used.
     */
    public function __construct($class_name = 'Pomm\Type\Segment', PgPoint $point_converter = null)
    {
        $this->class_name = $class_name;
        $this->point_converter = is_null($point_converter) ? new PgPoint() : $point_converter;
    }

    /**
     * @see Pomm\Converter\ConverterInterface
     */
    public function fromPg($data, $type = null)
    {
        if (empty($data))
        {
            return null;
        }

        $data = trim($data, "[]");
        $elts = preg_split('/[,\s]*(\([^\)]+\))[,\s]*|[,\s]+/', $data, 0, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);

        if (count($elts) !== 2)
        {
            throw new Exception(sprintf("Cannot parse segment data '%s'.", $data));
        }

        return new $this->class_name($this->point_converter->fromPg($elts[0]), $this->point_converter->fromPg($elts[1]));
    }

    /**
     * @see Pomm\Converter\ConverterInterface
     */
    public function toPg($data, $type = null)
    {
        if (! $data instanceof $this->class_name)
        {
            if (!is_object($data))
            {
                $type = gettype($data);
            }
            else
            {
                $type = get_class($data);
            }

            throw new Exception(sprintf("Converter PgLseg needs data to be an instance of '%s' ('%s' given).", $this->class_name, $type));
        }

        return sprintf("lseg(%s, %s)",
            $this->point_converter->toPg($data->point_a),
            $this->point_converter->toPg($data->point_b)
        );
    }
}
