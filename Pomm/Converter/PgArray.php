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

        if ($data !== "{NULL}")
        {
            $converter = $this->database
                ->getConverterForType($type);

            return array_map(function($val) use ($converter, $type) {
                    return $val !== "NULL" ? $converter->fromPg(str_replace('\\"', '"', $val), $type) : null;
                },
                    preg_split('/[,\s]*"((?:[^\\\\"]|\\\\.|"")+)"[,\s]*|[,\s]+/', 
                        str_replace('""', '"', trim($data, "{}")), 0, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE));
            }
        else
        {
            return array();
        }
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
            if (is_null($data))
            {
                return 'NULL';
            }

            throw new Exception(sprintf("Array converter toPg() data must be an array ('%s' given).", gettype($data)));
        }

        $converter = $this->database
            ->getConverterForType($type);

        return sprintf('ARRAY[%s]::%s[]', join(',', array_map(function ($val) use ($converter, $type) { 
                    return !is_null($val) ? $converter->toPg($val, $type) : 'NULL'; 
                }, $data)), $type);
    }
}

