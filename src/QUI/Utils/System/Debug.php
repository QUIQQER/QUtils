<?php

/**
 * This file contains \QUI\Utils\System\Debug
 */

namespace QUI\Utils\System;

use function is_string;
use function memory_get_usage;
use function microtime;
use function sprintf;

/**
 * Debug
 *
 * Log the system memory usage
 *
 * @author  www.pcsg.de (Henning Leutz)
 */
class Debug
{
    /**
     * marker lists
     *
     * @var array
     */
    public static array $times = [];

    /**
     * create the output flag
     *
     * @var boolean
     */
    public static bool $run = false;

    /**
     * debug the memory flag
     *
     * @var boolean
     */
    public static bool $debug_memory = false;

    /**
     * Set a Debug Marker
     *
     * @param boolean|string $step - (optional)
     */
    public static function marker(bool|string $step = false): void
    {
        if (!self::$run) {
            return;
        }

        $params = [];
        $params['time'] = microtime(true);

        if (self::$debug_memory) {
            $params['memory'] = ' MEMORY: ' . memory_get_usage();
        }

        if (is_string($step)) {
            $params['step'] = $step;
        }

        self::$times[] = $params;
    }

    /**
     * Return the Output
     *
     * @return string
     */
    public static function output(): string
    {
        if (!self::$run) {
            return '';
        }

        $str = $_SERVER['REQUEST_URI'] . "\n\n";

        $before_time = false;
        $before_key = false;

        $start = false;

        foreach (self::$times as $key => $params) {
            if (!$before_time) {
                $before_time = $params['time'];
                $before_key = $key;

                if (!empty($params['step'])) {
                    $before_key = $params['step'];
                }

                $start = $params['time'];
                continue;
            }

            if (!empty($params['step'])) {
                $key = $params['step'];
            }

            $str .= $before_key . ' -> ' . $key . ' : ';
            $str .= sprintf('%.3f', ($params['time'] - $before_time)) . "\n";

            $before_time = $params['time'];
            $before_key = $key;
        }

        $str .= "\nOverall: " . sprintf('%.3f', ($before_time - $start)) . " seconds\n\n";

        return $str;
    }
}
