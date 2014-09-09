<?php

namespace Pomm\Exception;

/**
 * Pomm\Exception - Pomm's Connection Exception class used to embed driver
 * error messages.
 *
 * @package Pomm
 * @uses \Exception
 * @version $id$
 * @copyright 2011 - 2013 Grégoire HUBERT
 * @author Grégoire HUBERT <hubert.greg@gmail.com>
 * @license X11 {@link http://opensource.org/licenses/mit-license.php}
 */
class ConnectionException extends Exception
{
    public function __construct($message)
    {
        $message .= '.';

        $last_error = @pg_last_error();
        if ($last_error !== false)
        {
            $message .= sprintf(' Driver said «%s».', $last_error);
        }

        parent::__construct($message);
    }
}

