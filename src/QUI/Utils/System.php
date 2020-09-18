<?php

/**
 * This file contains the QUI\Utils\System
 */

namespace QUI\Utils;

use QUI;
use QUI\Utils\System\File;

/**
 * Helper class for the system variables
 *
 * @author  www.pcsg.de (Henning Leutz)
 */
class System
{
    /**
     * The max memory limit for memUsageToHigh(), look at ::memUsageToHigh()
     *
     * @var integer
     */
    public static $memory_limit = 128;

    /**
     * Return the PHP Memory Limit in bytes
     *
     * @return integer
     */
    public static function getMemoryLimit()
    {
        return File::getBytes(\ini_get('memory_limit'));
    }

    /**
     * Return the used protocol
     *
     * @return string
     * @example QUI\Utils\System::getProtocol(); -> http:// or https://
     */
    public static function getProtocol()
    {
        if (self::isProtocolSecure()) {
            return 'https://';
        }

        return 'http://';
    }

    /**
     * Return true if the used protocol is https
     *
     * @return boolean
     */
    public static function isProtocolSecure()
    {
        if (isset($_SERVER['HTTPS'])) {
            $https = \strtolower($_SERVER['HTTPS']);

            if ($https == 'on') {
                return true;
            }

            if ($https == '1') {
                return true;
            }
        } elseif (isset($_SERVER['SERVER_PORT'])) {
            if ($_SERVER['SERVER_PORT'] == '443') {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the maximum file size which can be uploaded
     *
     * @return integer
     */
    public static function getUploadMaxFileSize()
    {
        return \min(
            (int)\ini_get('upload_max_filesize'),
            (int)\ini_get('post_max_size')
        );
    }

    /**
     * Checks the memory consumption
     *
     * If 80% of consumption was given returns true
     * If self::$memory_limit is not set | false | null, than always return false
     *
     * @return boolean
     */
    public static function memUsageToHigh()
    {
        if (!self::$memory_limit) {
            return false;
        }

        // 80% abfragen
        $usage = (int)(\memory_get_usage() / 1024 / 1000); // in MB
        $max   = (int)self::$memory_limit;
        $_max  = $max / 100 * 80; // 80%

        if ($_max < $usage) {
            return true;
        }

        return false;
    }

    /**
     * IP des Clients bekommen, auch durch Proxys
     *
     * @return string
     */
    public static function getClientIP()
    {
        // CloudFlare network
        if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
            $_SERVER['REMOTE_ADDR']    = $_SERVER["HTTP_CF_CONNECTING_IP"];
            $_SERVER['HTTP_CLIENT_IP'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
        }

        $client  = null;
        $forward = null;
        $remote  = null;

        if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $client = $_SERVER['HTTP_CLIENT_IP'];
        }

        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $forward = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }

        if (isset($_SERVER['REMOTE_ADDR'])) {
            $remote = $_SERVER['REMOTE_ADDR'];
        }

        if (\filter_var($client, FILTER_VALIDATE_IP)) {
            $ip = $client;
        } elseif (\filter_var($forward, FILTER_VALIDATE_IP)) {
            $ip = $forward;
        } else {
            $ip = $remote;
        }

        return $ip;
    }

    /**
     * Check if a shell function is callable
     *
     * @param string $function - Name of the shell function (e.g. exec, shell_exec, etc.)
     *
     * @return boolean
     */
    public static function isShellFunctionEnabled($function)
    {
        if (!\is_callable($function)) {
            return false;
        }

        $disabledFunctions = \explode(',', \ini_get('disable_functions'));

        return !\in_array($function, $disabledFunctions);
    }


    /**
     * Returns if a given system function (e.g. 'ls') is callable (via exec).
     * Requires exec to be enabled. If it's not, false will always be returned.
     *
     * @param string $function
     *
     * @return bool
     */
    public static function isSystemFunctionCallable($function)
    {
        if (!static::isShellFunctionEnabled('exec')) {
            return false;
        }

        exec("command -v {$function}", $output, $returnCode);

        return $returnCode == 0;
    }
}
