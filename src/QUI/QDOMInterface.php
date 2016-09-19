<?php

/**
 * This file contains \QUI\QDOMInterface
 */

namespace QUI;

/**
 * QUIQQER-DOM Class Interface
 *
 * The QDOM class emulate similar methods
 * like a DOMNode, its the main parent factory class
 *
 * @author www.pcsg.de (Henning Leutz)
 */
interface QDOMInterface
{
    /**
     * Exist the attribute in the object?
     *
     * @param string $name
     *
     * @return boolean
     */
    public function existsAttribute($name);

    /**
     * returns a attribute
     * if the attribute is not set, it returns false
     *
     * @param string $name
     *
     * @return mixed
     */
    public function getAttribute($name);

    /**
     * set an attribute
     *
     * @param string $name - name of the attribute
     * @param string|boolean|array|object $val - value of the attribute
     *
     * @return QDOM this
     */
    public function setAttribute($name, $val);

    /**
     * If you want to set more than one attribute
     *
     * @param array $attributes
     *
     * @return QDOM this
     */
    public function setAttributes($attributes);

    /**
     * Remove a attribute
     *
     * @param string $name
     *
     * @return boolean
     */
    public function removeAttribute($name);

    /**
     * Return all attributes
     *
     * @return array
     */
    public function getAttributes();
}
