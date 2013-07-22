<?php

/**
 * This file contains \QUI\Utils\ArrayHelper
 */

namespace QUI\Utils;

/**
 * Helper for array handling
 *
 * @author www.pcsg.de (Henning Leutz
 * @package com.pcsg.qutils
 */

class ArrayHelper
{
    /**
     * Checks if the array is associative
     *
     * @param array $array
     * @return Bool
     */
    static function isAssoc(array $array)
    {
        foreach ( $array as $key => $value )
        {
            if ( is_int( $key ) ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Converts an index array in an associative array
     *
     * @param array $array
     * @return array
     */
    static function toAssoc(array $array)
    {
        $result = array();

        for ( $i = 0, $len = count($array); $i < $len; $i++ ) {
            $result[ $array[$i] ] = true;
        }

        return $result;
    }

    /**
     * Converts an object to an array
     *
     * @param Object $obj
     * @return Array
     */
    static function objectToArray($obj)
    {
        $_arr = is_object( $obj ) ? get_object_vars( $obj ) : $obj;
        $arr  = array();

        if ( !is_array( $_arr ) ) {
            return $arr;
        }

        foreach ( $_arr as $key => $val )
        {
            if ( is_array( $val ) || is_object( $val ) ) {
                $val = self::objectToArray( $val );
            }

            $arr[ $key ] = $val;
        }

        return $arr;
    }

    /**
     * Converts an array to an object
     *
     * @param array $array
     * @return Object
     */
    static function arrayToObject($array=array())
    {
        // its the easiest way
        return (object)$array;
    }
}

?>