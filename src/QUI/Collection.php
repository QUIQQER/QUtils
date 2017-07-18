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

    //region Collection methods

    /**
     * Append an allowed child to the collection
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
     * Clears the complete collection
     */
    public function clear()
    {
        $this->children = array();
    }

    /**
     * Execute $function(value, key) for each children
     *
     * @param callable $function
     */
    public function each(callable $function)
    {
        foreach ($this->children as $key => $value) {
            $function($value, $key);
        }
    }

    /**
     * Returns first children in the collection or throws Exception
     *
     * @return mixed
     * @throws Exception
     */
    public function first()
    {
        if (empty($this->children)) {
            throw new Exception('Item not found, Collection ist empty');
        }

        return $this->children[0];
    }

    /**
     * Returns children at the key $key
     *
     * @param integer $key
     * @return mixed
     * @throws Exception
     */
    public function get($key)
    {
        if (empty($this->children) || !isset($this->children[$key])) {
            throw new Exception('Item not found, Collection ist empty');
        }

        return $this->children[$key];
    }

    /**
     * Insert an allowed child to a specific position
     * no child would be overwritten
     *
     * @param mixed $Child
     * @param bool $pos - starts at 0, if $pos is false = child appended to the end
     */
    public function insert($Child, $pos = false)
    {
        if (!$this->isAllowed($Child)) {
            return;
        }

        if ($pos === false || $this->length() > $pos) {
            $this->children[] = $Child;
            return;
        }

        $children = array();

        foreach ($this->children as $key => $Sibling) {
            echo $key . '-' . $pos . '<br>';

            if ($pos == $key) {
                $children[] = $Child;
            }

            $children[] = $Sibling;
        }

        $this->children = $children;
    }

    /**
     * Returns true if the collection is empty
     *
     * @return bool
     */
    public function isEmpty()
    {
        return !!$this->length();
    }

    /**
     * Returns true if the collection is not empty
     *
     * @return bool
     */
    public function isNotEmpty()
    {
        return !!$this->length();
    }

    /**
     * Returns last children in the collection or throws Exception
     *
     * @return mixed
     * @throws Exception
     */
    public function last()
    {
        if (empty($this->children)) {
            throw new Exception('Item not found, Collection ist empty');
        }

        $length = $this->length();

        return $this->children[$length - 1];
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
     * Applies the callback to each children of the collection
     *
     * @param callable $function
     * @return array
     */
    public function map(callable $function)
    {
        return array_map($function, $this->children);
    }

    /**
     * Sort the children using a user-defined comparison function
     * eq: $this->sort(function($a, $b) { })
     *
     * @param callable $function
     */
    public function sort(callable $function)
    {
        usort($this->children, $function);
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

    //endregion

    //region Helper

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

    //endregion

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
