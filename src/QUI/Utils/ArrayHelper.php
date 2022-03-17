<?php

/**
 * This file contains \QUI\Utils\ArrayHelper
 */

namespace QUI\Utils;

use function array_filter;
use function array_unique;
use function count;
use function explode;
use function get_object_vars;
use function is_array;
use function is_bool;
use function is_int;
use function is_object;

/**
 * Helper for array handling
 *
 * @author  www.pcsg.de (Henning Leutz
 */
class ArrayHelper
{
    /**
     * Checks if the array is associative
     *
     * @param array $array
     *
     * @return boolean
     */
    public static function isAssoc(array $array): bool
    {
        foreach ($array as $key => $value) {
            if (is_int($key)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Converts an index array in an associative array
     *
     * @param array $array
     *
     * @return array
     */
    public static function toAssoc(array $array): array
    {
        $result = [];

        for ($i = 0, $len = count($array); $i < $len; $i++) {
            $result[$array[$i]] = true;
        }

        return $result;
    }

    /**
     * Converts an object to an array
     *
     * @param object|array $obj
     *
     * @return array
     */
    public static function objectToArray($obj): array
    {
        $_arr = is_object($obj) ? get_object_vars($obj) : $obj;
        $arr  = [];

        if (!is_array($_arr)) {
            return $arr;
        }

        foreach ($_arr as $key => $val) {
            if (is_array($val) || is_object($val)) {
                $val = self::objectToArray($val);
            }

            $arr[$key] = $val;
        }

        return $arr;
    }

    /**
     * Converts an array to an object
     *
     * @param array $array
     *
     * @return Object
     */
    public static function arrayToObject(array $array = []): object
    {
        return (object)$array;
    }

    /**
     * Cleanup an array
     *
     * @param array|string $array
     * @param string $delimiter - default = ,
     * @return array
     */
    public static function cleanup($array, string $delimiter = ','): array
    {
        if (is_bool($array)) {
            return [];
        }

        if (!is_array($array)) {
            $array = explode($delimiter, $array);
        }

        return array_filter(array_unique($array));
    }
}
