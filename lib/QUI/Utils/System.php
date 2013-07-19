<?php

/**
 * This file contains the QUI\Utils\System
 */

namespace QUI\Utils;

/**
 * Helper class for the system variables
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.utils
 */

class System
{
    static $memory_limit = 128;

    /**
     * Return the used protocol
     *
     * @return String
     * @example QUI\Utils\System::getProtocol(); -> http:// or https://
     */
    static function getProtocol()
    {
        if ( !isset( $_SERVER['HTTPS'] ) || $_SERVER['HTTPS'] != "on" ) {
              return 'http://';
        }

        return 'https://';
    }

    /**
     * Returns the maximum file size which can be uploaded
     *
     * @return Integer
     */
    static function getUploadMaxFileSize()
    {
        return min(
            (int)ini_get('upload_max_filesize'),
            (int)ini_get('post_max_size')
        );
    }

    /**
     * Checks the memory consumption
     *
     * If 80% of consumption was given returns true
     * If self::$memory_limit is not set, will always return false
     *
     * @return Bool
     */
    static function memUsageToHigh()
    {
        if ( !self::$memory_limit ) {
            return false;
        }

        // 80% abfragen
        $usage = (int)(memory_get_usage() / 1024 / 1000); // in MB
        $max   = (int)self::$memory_limit;
        $_max  = $max / 100 * 80; // 80%

        if ( $_max < $usage ) {
            return true;
        }

        return false;
    }

    /**
     * IP des Clients bekommen, auch durch Proxys
     *
     * @return String
     */
    static function getClientIP()
    {
        // durch proxy
        if ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        }

        if ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
            return $_SERVER['REMOTE_ADDR'];
        }

        return '';
    }
}
