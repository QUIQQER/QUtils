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
class Singleton
{
    /**
     * @var null
     */
    protected static $Instance = null;

    /**
     * Return the instance
     *
     * @return Singleton
     */
    public static function getInstance()
    {
        if (self::$Instance === null) {
            self::$Instance = new self();
        }

        return self::$Instance;
    }
}