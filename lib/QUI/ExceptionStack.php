<?php

/**
 * This file contains the \QUI\Exception
 */

namespace QUI;

/**
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qutils
 */

class ExceptionStack extends Exception
{
    /**
     * Container for exceptions
     *
     * @var array
     */
    protected $_list = array();

    /**
     * Adds an exception to the stack
     *
     * @param Exception $Exception
     */
    public function addException($Exception)
    {
        $this->_list[] = $Exception;
    }

    /**
     * Returns current list with collected exceptions
     *
     * @return array
     */
    public function getExceptionList()
    {
        return $this->_list;
    }

    /**
     * Checks if the exception is empty
     *
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->_list);
    }
}
