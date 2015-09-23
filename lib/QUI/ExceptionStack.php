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
     * @var array
     */
    protected $_list = array();

    /**
     * @param Exception $Exception
     */
    public function addException($Exception)
    {
        $this->_list[] = $Exception;
    }

    /**
     * @return array
     */
    public function getExceptionList()
    {
        return $this->_list;
    }

    /**
     *
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->_list);
    }
}
