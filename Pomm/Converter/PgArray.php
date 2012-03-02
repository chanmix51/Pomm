<?php

namespace Pomm\Converter;

use Pomm\Converter\ConverterInterface;
use Pomm\Exception\Exception;

/**
 * Pomm\Converter\pgArray - Array converter
 * 
 * @package Pomm
 * @version $id$
 * @copyright 2011 Grégoire HUBERT 
 * @author Grégoire HUBERT <hubert.greg@gmail.com>
 * @license X11 {@link http://opensource.org/licenses/mit-license.php}
 */

class pgArray implements ConverterInterface
{
    protected $database;

    public function __construct(\Pomm\Connection\Database $database)
    {
        $this->database = $database;
    }

    /**
     * @see ConverterInterface
     **/
    public function fromPg($data, $type = null)
    {
        if (is_null($type))
        {
            throw new Exception(sprintf('Array converter must be given a type.'));
        }

        $converter = $this->database
            ->getConverterForType($type);
        return array_map(function($val) use ($converter, $type) {
                return $converter->fromPg($val, $type);
                        },
                        preg_split('/[,\s]*"((?:[^\\\\"]|\\\\.|"")+)"[,\s]*|[,\s]+/', str_replace('""', '"', trim($data, "{}")), 0, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE));
    }

    /**
     * @see ConverterInterface
     **/
    public function toPg($data, $type = null)
    {
        if (is_null($type))
        {
            throw new Exception(sprintf('Array converter must be given a type.'));
        }
        if (!is_array($data))
        {
            throw new Exception(sprintf("Array converter toPg() data must be an array ('%s' given).", gettype($data)));
        }

        $converter = $this->database
            ->getConverterForType($type);

        return sprintf('ARRAY[%s]::%s[]', join(',', array_map(function ($val) use ($converter, $type) { 
                    return $converter->toPg($val, $type); 
                }, $data)), $type);
    }
}

