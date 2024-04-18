<?php

/**
 * This file contains \QUI\QDOMInterface
 */

namespace QUI;

/**
 * QUIQQER-DOM Class Interface
 *
 * The QDOM class emulate similar methods
 * like a DOMNode, it's the main parent factory class
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
    public function existsAttribute(string $name): bool;

    /**
     * returns an attribute
     * if the attribute is not set, it returns false
     *
     * @param string $name
     * @return mixed
     */
    public function getAttribute(string $name): mixed;

    /**
     * set an attribute
     *
     * @param string $name - name of the attribute
     * @param mixed $value
     */
    public function setAttribute(string $name, mixed $value): void;

    /**
     * If you want to set more than one attribute
     *
     * @param array $attributes
     */
    public function setAttributes(array $attributes): void;

    /**
     * Remove a attribute
     *
     * @param string $name
     * @return boolean
     */
    public function removeAttribute(string $name): bool;

    /**
     * Return all attributes
     *
     * @return array
     */
    public function getAttributes(): array;
}
