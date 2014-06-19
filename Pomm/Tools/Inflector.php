<?php

namespace Pomm\Tools;

/**
 * Pomm\Tools\Inflector
 *
 * @package Pomm
 * @version $id$
 * @copyright 2011 - 2013 Grégoire HUBERT
 * @author Fabien D. <fabien at myprod.net>
 * @license X11 {@link http://opensource.org/licenses/mit-license.php}
 */
class Inflector
{
    /**
     * Camelizes a string.
     *
     * @param string $id A string to camelize
     *
     * @return string The camelized string
     */
    public static function camelize($id)
    {
        return preg_replace_callback(
            '/(^|_|\.)+(.)/',
            function ($match) {
                return ('.' === $match[1] ? '_' : '').strtoupper($match[2]);
            },
            $id
        );
    }

    /**
     * A string to underscore.
     *
     * @param string $id The string to underscore
     *
     * @return string The underscored string
     */
    public static function underscore($id)
    {
        return strtolower(preg_replace(array('/([A-Z]+)([A-Z][a-z])/', '/([a-z\d])([A-Z])/'), array('\\1_\\2', '\\1_\\2'), strtr($id, '_', '.')));
    }
}
