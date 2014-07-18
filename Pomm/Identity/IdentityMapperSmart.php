<?php

namespace Pomm\Identity;

use Pomm\Object\BaseObject;

/**
 * Pomm\Identity\IdentityMapperNone - "smart" identity mapper
 *
 * @package Pomm
 * @uses Pomm\Object\BaseObject
 * @version $id$
 * @copyright 2011 - 2013 Grégoire HUBERT
 * @author Grégoire HUBERT <hubert.greg@gmail.com>
 * @license X11 {@link http://opensource.org/licenses/mit-license.php}
 */
class IdentityMapperSmart extends IdentityMapperStrict
{
    /**
     * {@inheritdoc}
     */
    public function getInstance(BaseObject $object, Array $pk_fields)
    {
        if (count($pk_fields) == 0) {
            return $object;
        }

        $index = $this->getSignature($object, $object->get($pk_fields));

        if (array_key_exists($index, $this->mapper)) {
            $this->mapper[$index]->hydrate(array_merge($object->getFields(), $this->mapper[$index]->getFields()));
        } else {
            $this->mapper[$index] = $object;
        }

        return $this->mapper[$index];
    }
}
