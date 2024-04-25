<?php

/**
 * This file contains \QUI\Utils\Bool
 */

namespace QUI\Utils;

use function is_bool;
use function is_integer;

/**
 * Helper for bool type handling
 *
 * @author  www.pcsg.de (Henning Leutz
 */
class BoolHelper
{
    /**
     * internal var
     *
     * @var string|boolean
     */
    public string|bool $bool;

    /**
     * constructor
     *
     * @param boolean|string $bool
     */
    public function __construct(bool|string $bool)
    {
        $this->bool = (bool)$bool;
    }

    /**
     * Converts JavaScript Boolean values ​​for PHP
     *
     * @param boolean|string|int $value
     *
     * @return boolean
     */
    public static function JSBool(bool|string|int $value): bool|string
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
