<?php

/**
 * This file contains \QUI\Utils\Bool
 */

namespace QUI\Utils;

/**
 * Helper for bool type handling
 *
 * @author  www.pcsg.de (Henning Leutz
 * @package com.pcsg.qui.utils
 */

class BoolHelper
{
    /**
     * internal var
     *
     * @var string|boolean
     */
    public $bool;

    /**
     * constructor
     *
     * @param string|boolean $bool
     */
    public function __construct($bool)
    {
        $this->bool = (bool)$bool;
    }

    /**
     * Converts JavaScript Boolean values ​​for PHP
     *
     * @param string|boolean $value
     *
     * @return boolean
     */
    public static function JSBool($value)
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_integer($value)) {
            if ($value == 1) {
                return true;
            }

            return false;
        }

        if ($value == 'true' || $value == '1') {
            return true;
        }

        if ($value == 'false' || $value == '0') {
            return false;
        }

        return $value;
    }
}
