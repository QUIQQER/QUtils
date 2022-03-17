<?php

/**
 * This file contains QUI\Utils\Singleton
 */

namespace QUI\Utils;

use function get_called_class;

/**
 * Class Singleton
 */
abstract class Singleton
{
    /**
     * @var array
     */
    protected static array $instances = [];

    /**
     * Return the instance
     *
     * @return static
     */
    public static function getInstance()
    {
        $class = get_called_class();

        if (!isset(self::$instances[$class])) {
            self::$instances[$class] = new $class();
        }

        return self::$instances[$class];
    }
}
