<?php

/**
 * This file contains \QUI\QDOM
 */

namespace QUI;

/**
 * QUIQQER-DOM Class
 *
 * The QDOM class emulate similar methods
 * like a DOMNode, its the main parent factory class
 *
 * @package com.pcsg.qutils
 * @author  www.pcsg.de (Henning Leutz)
 */

class QDOM
{
    /**
     * Internal list of attributes
     *
     * @var array
     */
    protected $_attributes = array();

    /**
     * Exist the attribute in the object?
     *
     * @param string $name
     *
     * @return boolean
     */
    public function existsAttribute($name)
    {
        return isset($this->_attributes[$name]) ? true : false;
    }

    /**
     * returns a attribute
     * if the attribute is not set, it returns false
     *
     * @param string $name
     *
     * @return mixed
     */
    public function getAttribute($name)
    {
        if (isset($this->_attributes[$name])) {
            return $this->_attributes[$name];
        }

        return false;
    }

    /**
     * set an attribute
     *
     * @param string $name - name of the attribute
     * @param string|boolean|array|object $val - value of the attribute
     *
     * @return QDOM this
     */
    public function setAttribute($name, $val)
    {
        $this->_attributes[$name] = $val;

        return $this;
    }

    /**
     * If you want to set more than one attribute
     *
     * @param array $attributes
     *
     * @return QDOM this
     */
    public function setAttributes($attributes)
    {
        if (!is_array($attributes)) {
            return $this;
        }

        foreach ($attributes as $key => $value) {
            $this->setAttribute($key, $value);
        }

        return $this;
    }

    /**
     * Remove a attribute
     *
     * @param string $name
     *
     * @return boolean
     */
    public function removeAttribute($name)
    {
        if (isset($this->_attributes[$name])) {
            unset($this->_attributes[$name]);
        }

        return true;
    }

    /**
     * Return all attributes
     *
     * @return array
     * @deprecated getAllAttributes is depricated use getAttributes()
     */
    public function getAllAttributes()
    {
        return $this->getAttributes();
    }

    /**
     * Return all attributes
     *
     * @return array
     */
    public function getAttributes()
    {
        return $this->_attributes;
    }

    /**
     * Return the class type
     *
     * @return string
     */
    public function getType()
    {
        return get_class($this);
    }

    /**
     * Return the object as string
     *
     * @return string
     */
    public function __toString()
    {
        if ($this->getAttribute('name')) {
            return 'Object ' . get_class($this) . '(' . $this->getAttribute('name')
                   . ');';
        }

        return 'Object ' . get_class($this) . '();';
    }
}
