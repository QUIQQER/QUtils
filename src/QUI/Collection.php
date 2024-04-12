<?php

/**
 * This file contains QUI\Collection
 */

namespace QUI;

use ArrayAccess;
use ArrayIterator;
use IteratorAggregate;

use function array_filter;
use function array_keys;
use function array_map;
use function array_merge;
use function count;
use function get_called_class;
use function get_class;
use function in_array;
use function is_array;
use function is_null;
use function usort;

/**
 * Class Collection
 * Collects children from a specific class / classes
 * To specify allowed types you should extend this class.
 * Then set the $allowed variable there.
 */
class Collection implements IteratorAggregate, ArrayAccess
{
    /**
     * List of children
     *
     * @var array
     */
    protected array $children = [];

    /**
     * List of allowed children classes.
     * You have to extend this class to set the allowed types.
     *
     * @var array
     */
    protected array $allowed = [];

    /**
     * Collection constructor.
     *
     * @param array $children - list of children
     */
    public function __construct(array $children = [])
    {
        foreach ($children as $Child) {
            $this->append($Child);
        }
    }

    /**
     * @param array $params
     * @return Collection
     */
    public static function getInstance(array $params = []): Collection
    {
        $children = [];

        if (isset($params['children']) && is_array($params['children'])) {
            $children = $params['children'];
        }

        $class = get_called_class();

        return new $class($children);
    }

    //region Collection methods

    /**
     * Append an allowed child to the collection.
     * If $key is set, it's placed at the position specified in $key.
     *
     * @param mixed $Child
     * @param int|null $key
     */
    public function append(mixed $Child, int $key = null): void
    {
        if ($this->isAllowed($Child)) {
            if (is_null($key)) {
                $this->children[] = $Child;
            } else {
                $this->children[$key] = $Child;
            }
        }
    }

    /**
     * Merges one or more collections into this collection.
     *
     * @param Collection ...$Collections
     */
    public function merge(...$Collections): void
    {
        foreach ($Collections as $Collection) {
            // Check if the given collection is of the same type as our collection.
            // If it is, we can just merge it's content to our children.
            // instanceof can not be used here because it would allow more specific collections.
            // More specific collections could allow other children than our collection allows.
            if (get_class($Collection) == get_class($this)) {
                $this->children = array_merge($this->children, $Collection->toArray());
                continue;
            }

            // Append every child individually, to make sure only allowed children are added to our collection
            foreach ($Collection->toArray() as $Child) {
                $this->append($Child);
            }
        }
    }

    /**
     * Clears the complete collection
     */
    public function clear(): void
    {
        $this->children = [];
    }

    /**
     * Alias for length()
     *
     * @return int
     */
    public function count(): int
    {
        return $this->length();
    }

    /**
     * Execute $function(value, key) for each child
     *
     * @param callable $function
     */
    public function each(callable $function): void
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
    public function first(): mixed
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
    public function get(int $key): mixed
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
     * @param bool|int $pos - starts at 0, if $pos is false = child appended to the end
     */
    public function insert(mixed $Child, bool|int $pos = false): void
    {
        if (!$this->isAllowed($Child)) {
            return;
        }

        if ($pos === false || $this->length() <= $pos) {
            $this->children[] = $Child;

            return;
        }

        $children = [];

        foreach ($this->children as $key => $Sibling) {
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
    public function isEmpty(): bool
    {
        return empty($this->children);
    }

    /**
     * Returns true if the collection is not empty
     *
     * @return bool
     */
    public function isNotEmpty(): bool
    {
        return !empty($this->children);
    }


    /**
     * Returns true if an element is set at the given key.
     *
     * @param $key
     *
     * @return boolean
     */
    public function isSet($key): bool
    {
        return isset($this->children[$key]);
    }

    /**
     * Returns last children in the collection or throws Exception
     *
     * @return mixed
     * @throws Exception
     */
    public function last(): mixed
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
    public function length(): int
    {
        return count($this->children);
    }

    /**
     * Applies the callback to each child of the collection
     *
     * @param callable $function
     * @return array
     */
    public function map(callable $function): array
    {
        return array_map($function, $this->children);
    }

    /**
     * Sort the children using a user-defined comparison function
     * eq: $this->sort(function($a, $b) { })
     *
     * @param callable $function
     */
    public function sort(callable $function): void
    {
        usort($this->children, $function);
    }

    /**
     * Returns whether the collection contains the given child or not.
     *
     * @param $Child
     * @return bool
     */
    public function contains($Child): bool
    {
        return in_array($Child, $this->children);
    }

    /**
     * Converts the collection to an array
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->children;
    }


    /**
     * Returns a new collection filtered by the function passed as $callback
     * Uses array_filter function internally.
     * @see http://php.net/manual/de/function.array-filter.php
     *
     * @param callable $callback
     * @param int $flag
     *
     * @return $this
     */
    public function filter(callable $callback, int $flag = 0): Collection
    {
        return new $this(array_filter($this->children, $callback, $flag));
    }

    //endregion

    //region Helper

    /**
     * Checks if the child is allowed in the collection
     *
     * @param mixed $Child
     * @return bool
     */
    protected function isAllowed(mixed $Child): bool
    {
        $allowed = $this->allowed;
        $key = array_keys($this->allowed);

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
     * @return ArrayIterator
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->children);
    }

    /**
     * @param mixed $offset
     *
     * @return bool
     *
     * @deprecated Use isSet($offset) instead.
     */
    public function offsetExists(mixed $offset): bool
    {
        return $this->isSet($offset);
    }

    /**
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->children[$offset] ?? null;
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     *
     * @deprecated Use append($Child, $offset) instead
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->append($value, $offset);
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->children[$offset]);
    }

    //endregion
}
