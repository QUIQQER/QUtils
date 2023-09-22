<?php

/**
 * This file contains the \QUI\Exception
 */

namespace QUI;

/**
 *
 * @author  www.pcsg.de (Henning Leutz)
 */
class ExceptionStack extends Exception
{
    /**
     * Container for exceptions
     *
     * @var array
     */
    protected $list = [];

    /**
     * Adds an exception to the stack
     *
     * @param Exception|\Exception $Exception
     */
    public function addException($Exception)
    {
        $this->list[] = $Exception;

        $message = '';

        /* @var $Exc Exception */
        foreach ($this->list as $Exc) {
            $message .= $Exc->getMessage() . "\n";
        }

        $this->message = $message;
        $this->code = $Exception->getCode();
    }

    /**
     * Returns current list with collected exceptions
     *
     * @return array
     */
    public function getExceptionList()
    {
        return $this->list;
    }

    /**
     * Checks if the exception is empty
     *
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->list);
    }

    /**
     * Return the context data
     *
     * @return array
     */
    public function getContext()
    {
        $context = [];

        /* @var $Exc Exception */
        foreach ($this->list as $Exc) {
            $_context = $Exc->getContext();
            $_context['Exception'] = $Exc->getMessage();
            $_context['Trace'] = $Exc->getTrace();

            $context[] = $_context;
        }

        return $context;
    }
}
