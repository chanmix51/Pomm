<?php

namespace Pomm\Converter;

use Pomm\Converter\ConverterInterface;
use Pomm\Exception\Exception as PommException;

/**
 * Pomm\Converter\PgJSON -- JSON converter
 *
 * @package Pomm
 * @version $id$
 * @copyright 2011 - 2013 Grégoire HUBERT
 * @author Grégoire HUBERT <hubert.greg@gmail.com>
 * @license X11 {@link http://opensource.org/licenses/mit-license.php}
 */
class PgJSON implements ConverterInterface
{

    protected $output_type;

    const OUTPUT_ARRAYS = true;
    const OUTPUT_OBJECTS = false;

    /**
     * __construct
     *
     * @param output_type arrays or objects (default Arrays)
     */
    public function __construct($output_type = true)
    {
        $this->output_type = $output_type;
    }

    /**
     * @see Pomm\Converter\ConverterInterface
     */
    public function toPg($data, $type = null)
    {
        if ($this->output_type === static::OUTPUT_ARRAYS)
        {
            if (!is_array($data))
            {
                throw new PommException(sprintf("Json converter 'toPg()' with OUTPUT_ARRAYS expects data to be array. '%s' given.", gettype($data)));
            }
        }
        else
        {
            if (!(is_object($data) or $data instanceOf \StdClass))
            {
                throw new PommException(sprintf("Json converter 'toPg()' with OUTPUT_OBJECTS expecte data to be instance of StdClass, '%s' given", get_class($data)));
            }
        }

        $data = json_encode($data);
        $type = is_null($type) ? '' : sprintf("%s ", $type);
        $data = sprintf("%s'%s'",  $type, $data);

        return $data;
    }

    /**
     * @see Pomm\Converter\ConverterInterface
     */
    public function fromPg($data, $type = null)
    {
        return json_decode($data, $this->output_type);
    }
}

