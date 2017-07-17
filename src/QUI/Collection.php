<?php

/**
 * This file contains QUI\Collection
 */

namespace QUI;

/**
 * Class Collection
 * Collects children from a specific class / classes
 *
 * @package QUI
 */
class Collection implements \IteratorAggregate, \ArrayAccess
{
    /**
     * List of children
     *
     * @var array
     */
    protected $children = array();

    /**
     * List of allowed children classes
     *
     * @var array
     */
    protected $allowed = array();

    /**
     * Collection constructor.
     *
     * @param array $children - list of children
     */
    public function __construct($children = array())
    {
        foreach ($children as $Child) {
            $this->append($Child);
        }
    }

    /**
     * @param array $params
     * @return Collection
     */
    public static function getInstance($params = array())
    {
        $children = array();
        $allowed  = array();

        if (isset($params['children']) && is_array($params['children'])) {
            $children = $params['children'];
        }

        if (isset($params['allowed']) && is_array($params['allowed'])) {
            $allowed = $params['allowed'];
        }

        return new Collection($children, $allowed);
    }

    /**
     * Append a the allowed child to the collection
     *
     * @param mixed $Child
     */
    public function append($Child)
    {
        if ($this->isAllowed($Child)) {
            $this->children[] = $Child;
        }
    }

    /**
     * Applies the callback to the elements of the collection
     *
     * @param callable $callback
     * @return array
     */
    public function map($callback)
    {
        return array_map($callback, $this->children);
    }

    /**
     * Return the number of children
     *
     * @return int
     */
    public function length()
    {
        return count($this->children);
    }

    /**
     * Converts the collection to an array
     *
     * @return array
     */
    public function toArray()
    {
        return $this->children;
    }

    /**
     * Checks if the child is allowed in the collection
     *
     * @param mixed $Child
     * @return bool
     */
    protected function isAllowed($Child)
    {
        $allowed = $this->allowed;
        $key     = array_keys($this->allowed);

        if (empty($allowed)) {
            return true;
        }

        if (isset($key[get_class($Child)])) {
            return true;
        }

        foreach ($allowed as $allow) {
            if ($Child instanceof $allow) {
                return true;
            }
        }

        return false;
    }

    //region interfaces API

    /**
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->children);
    }

    /**
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->children[$offset]);
    }

    /**
     * @param mixed $offset
     * @return mixed|null
     */
    public function offsetGet($offset)
    {
        return isset($this->children[$offset]) ? $this->children[$offset] : null;
    }

    /**
     * @param mixed $offset
     * @param mixed $Child
     */
    public function offsetSet($offset, $Child)
    {
        if ($this->isAllowed($Child) === false) {
            return;
        }

        if (is_null($offset)) {
            $this->children[] = $Child;
        } else {
            $this->children[$offset] = $Child;
        }
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        unset($this->children[$offset]);
    }

    //endregion
}
