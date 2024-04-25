<?php

/**
 * This file contains the \QUI\Database\Exception
 */

namespace QUI\Database;

/**
 * The Exception class for database operations
 *
 * @author  www.pcsg.de (Henning Leutz)
 */
class Exception extends \QUI\Exception
{
    public function __construct($message = null, int|string $code = 0, $context = [])
    {
        parent::__construct($message, (int)$code, $context);
    }
}
