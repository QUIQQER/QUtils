<?php

/**
 * This file contains \QUI\Utils\System\Debug
 */

namespace QUI\Utils\System;

/**
 * Debug
 *
 * Log the system memory usage
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.system
 */

class Debug
{
    /**
     * marker lists
     * @var array
     */
    static $times = array();

    /**
     * create the output flag
     * @var Bool
     */
    static $run = false;

    /**
     * debug the memory flag
     * @var Bool
     */
    static $debug_memory = false;

    /**
     * Set a Debug Marker
     *
     * @param String|Bool $step - (optional)
     */
    static function marker($step=false)
    {
        if ( self::$run == false ) {
            return;
        }

        $params = array();
        $params['time'] = microtime( true );

        if ( self::$debug_memory ) {
            $params['memory'] = ' MEMORY: ' . memory_get_usage();
        }

        if ( is_string( $step ) ) {
            $params['step'] = $step;
        }

        self::$times[] = $params;
    }

    /**
     * Return the Output
     *
     * @return String
     */
    static function output()
    {
        if ( self::$run == false ) {
            return '';
        }

        $str = $_SERVER['REQUEST_URI'] ."\n\n";

        $before_time = false;
        $before_key  = false;

        $start = false;

        foreach ( self::$times as $key => $params )
        {
            if ( $before_time == false )
            {
                $before_time = $params['time'];
                $before_key  = $key;

                $start = $params['time'];
                continue;
            }

            $str .= $before_key .' -> '. $key .' : ';
            $str .= sprintf( '%.3f', ($params['time'] - $before_time) ) ."\n";

            $before_time = $params['time'];
            $before_key  = $key;
        }

        $str .= "\nOverall: ". sprintf( '%.3f', ($before_time - $start) ) ." Sekunden\n\n";

        return $str;
    }
}
