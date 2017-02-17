<?php

/**
 * This file contains QUI\Utils\Singleton
 */
namespace QUI\Utils;

/**
 * Class Singleton
 *
 * @package QUI\Utils
 */
abstract class Singleton
{
    /**
     * @var null
     */
    protected static $instances = array();

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
