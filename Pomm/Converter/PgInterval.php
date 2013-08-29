<?php

namespace Pomm\Converter;

use Pomm\Converter\ConverterInterface;
use Pomm\Exception\Exception;

/**
 * Pomm\Converter\PgInterval - Date interval converter
 *
 * @package Pomm
 * @version $id$
 * @copyright 2011 Grégoire HUBERT
 * @author Grégoire HUBERT <hubert.greg@gmail.com>
 * @license X11 {@link http://opensource.org/licenses/mit-license.php}
 */
class PgInterval implements ConverterInterface
{
    /**
     * @see Pomm\Converter\ConverterInterface
     */
    public function fromPg($data, $type = null)
    {
        // if IntervalStyle is 'iso_8601'
        if (preg_match("/^P/", $data))
        {
            return new \DateInterval($data);
        }

        // if IntervalStyle is 'postgres'
        if (preg_match("/(?:([0-9]+) years? ?)?(?:([0-9]+) mons? ?)?(?:([1-9]+) days? ?)?(?:([0-9]{1,2}):([0-9]{1,2}):([0-9]+))?/", $data, $matchs))
        {
            return \DateInterval::createFromDateString(
                sprintf("%d years %d months %d days %d hours %d minutes %d seconds",
                    array_key_exists(1, $matchs) ? (is_null($matchs[1]) ? 0 : (int) $matchs[1]) : 0,
                    array_key_exists(2, $matchs) ? (is_null($matchs[2]) ? 0 : (int) $matchs[2]) : 0,
                    array_key_exists(3, $matchs) ? (is_null($matchs[3]) ? 0 : (int) $matchs[3]) : 0,
                    array_key_exists(4, $matchs) ? (is_null($matchs[4]) ? 0 : (int) $matchs[4]) : 0,
                    array_key_exists(5, $matchs) ? (is_null($matchs[5]) ? 0 : (int) $matchs[5]) : 0,
                    array_key_exists(6, $matchs) ? (is_null($matchs[6]) ? 0 : (int) $matchs[6]) : 0
                ));
        }

        throw new Exception(sprintf("Data '%s' is not a supported pg interval representation.", $data));
    }

    /**
     * @see Pomm\Converter\ConverterInterface
     */
    public function toPg($data, $type = null)
    {
        if (!$data instanceof \DateInterval)
        {
            $data = \DateInterval::createFromDateString($data);
        }

        return sprintf("interval '%s'", $data->format('%Y years %M months %D days %H:%i:%S'));
    }
}
