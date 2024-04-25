<?php

/**
 * This file contains \QUI\QDOM
 */

namespace QUI;

use function get_class;

/**
 * QUIQQER-DOM Class
 *
 * The QDOM class emulate similar methods
 * like a DOMNode, it's the main parent factory class
 *
 * @author  www.pcsg.de (Henning Leutz)
 */
class QDOM
{
    /**
     * Internal list of attributes
     *
     * @var array
     */
    protected array $attributes = [];

    /**
     * Exist the attribute in the object?
     *
     * @param string $name
     * @return boolean
     */
    public function existsAttribute(string $name): bool
    {
        return isset($this->attributes[$name]);
    }

    /**
     * returns an attribute
     * if the attribute is not set, it returns false
     *
     * @param string $name
     * @return mixed
     */
    public function getAttribute(string $name): mixed
    {
        if (isset($this->attributes[$name])) {
            return $this->attributes[$name];
        }

        return false;
    }

    /**
     * set an attribute
     *
     * @param string $name - name of the attribute
     * @param mixed $value
     */
    public function setAttribute(string $name, mixed $value): void
    {
        $this->attributes[$name] = $value;
    }

    /**
     * If you want to set more than one attribute
     *
     * @param array $attributes
     */
    public function setAttributes(array $attributes): void
    {
        foreach ($attributes as $key => $value) {
            $this->setAttribute($key, $value);
        }
    }

    /**
     * Remove a attribute
     *
     * @param string $name
     * @return boolean
     */
    public function removeAttribute(string $name): bool
    {
        if (isset($this->attributes[$name])) {
            unset($this->attributes[$name]);
        }

        return true;
    }

    /**
     * Return all attributes
     *
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Return the class type
     *
     * @return string
     */
    public function getType(): string
    {
        return get_class($this);
    }

    /**
     * Checks if the object is of this class or has this class as one of its parents
     *
     * @param string $className
     * @return bool
     */
    public function isInstanceOf(string $className): bool
    {
        return $this instanceof $className;
    }
}
