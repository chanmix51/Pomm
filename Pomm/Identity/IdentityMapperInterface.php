<?php

namespace Pomm\Identity;

use Pomm\Object\BaseObject;

/**
 * Pomm\Identity\IdentityMapperInterface - Interface for identity mappers
 *
 * @package Pomm
 * @uses Pomm\Object\BaseObject
 * @version $id$
 * @copyright 2011 - 2013 Grégoire HUBERT
 * @author Grégoire HUBERT <hubert.greg@gmail.com>
 * @license X11 {@link http://opensource.org/licenses/mit-license.php}
 */
interface IdentityMapperInterface
{
    /**
     * getInstance
     *
     * Sets the given model instance to de data mapper if not
     * set already. Return the set instance.
     *
     * @param BaseObject $object    Entity instance.
     * @param array      $pk_fields Unique identifier for that entity.
     * @return BaseObject
     */
    public function getInstance(BaseObject $object, array $pk_fields);


    /**
     * clear
     *
     * Remove the instance from the IM if it exists.
     *
     * @param BaseObject $object Entity instance.
     * @param array $pk_fields  Unique identifier for that entity.
     */
    public function clear(BaseObject $object, array $pk_fields);

    /**
     * flush
     *
     * Flush the identity mapper.
     */
    public function flush();
}
