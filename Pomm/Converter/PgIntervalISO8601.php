<?php

namespace Pomm\Converter;

use Pomm\Exception\Exception as PommException;

/**
 * Pomm\Converter\PgIntervalISO8601 - ISO8601 interval converter
 *
 * @package Pomm
 * @version $id$
 * @copyright 2011 - 2013 Grégoire HUBERT
 * @author Grégoire HUBERT <hubert.greg@gmail.com>
 * @license X11 {@link http://opensource.org/licenses/mit-license.php}
 */
class PgIntervalISO8601 extends PgInterval
{
    /**
     * @see Pomm\Converter\ConverterInterface
     */
    public function fromPg($data, $type = null)
    {
        if ($data === null || $data === '') {
            return null;
        }

        try {
            return new \DateInterval($data);
        } catch (\Exception $e) {
            throw new PommException(sprintf("Data '%s' is not an ISO8601 interval representation.", $data));
        }
    }
}
