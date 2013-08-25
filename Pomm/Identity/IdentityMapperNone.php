<?php

namespace Pomm\Identity;

use Pomm\Object\BaseObject;

/**
 * Pomm\Identity\IdentityMapperNone - Like no identity mapper
 *
 * @package Pomm
 * @uses Pomm\Object\BaseObject
 * @version $id$
 * @copyright 2011 - 2013 Grégoire HUBERT
 * @author Grégoire HUBERT <hubert.greg@gmail.com>
 * @license X11 {@link http://opensource.org/licenses/mit-license.php}
 */
class IdentityMapperNone implements IdentityMapperInterface
{
    /**
     * {@inheritdoc}
     */
    public function getInstance(BaseObject $object, array $pk_fields)
    {
        return $object;
    }
    /**
     * {@inheritdoc}
     */
    public function flush()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function clear(BaseObject $object, array $pk_fields)
    {
    }
}
