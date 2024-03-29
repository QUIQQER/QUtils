<?php

/**
 * This file contains the \QUI\Exception
 */

namespace QUI;

use QUI;

use function class_exists;
use function get_class;
use function implode;
use function is_array;

/**
 * The Main Exception class for QUIQQER CMS and QUI Utils
 *
 * @author  www.pcsg.de (Henning Leutz)
 */
class Exception extends \Exception
{
    /**
     * Internal list of attributes
     *
     * @var array
     */
    protected $attributes = [];

    /**
     * context data
     *
     * @var array
     */
    protected $context = [];

    /**
     * Constructor
     *
     * @param string|array $message - Text der Exception
     * @param integer $code - Errorcode der Exception
     * @param array $context - [optional] Context data, which data
     */
    public function __construct($message = null, $code = 0, $context = [])
    {
        if (is_array($message)) {
            if (!isset($message[0]) || !isset($message[1])) {
                $message = implode(',', $message);
            } else {
                $params = [];

                if (isset($message[2])) {
                    $params = $message[2];
                }

                $context['locale'] = $message;

                if (class_exists('QUI')) {
                    $message = QUI::getUserBySession()->getLocale()->get(
                        $message[0],
                        $message[1],
                        $params
                    );
                }
            }
        }

        parent::__construct((string)$message, (int)$code);

        if (!empty($context)) {
            $this->context = $context;
        }
    }

    /**
     * Return the Exception type
     *
     * @return string
     */
    public function getType()
    {
        return get_class($this);
    }

    /**
     * Return the context data
     *
     * @return array
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * Return the Exception as an array
     *
     * @return array
     */
    public function toArray()
    {
        $attributes = $this->attributes;

        $attributes['code'] = $this->getCode();
        $attributes['message'] = $this->getMessage();
        $attributes['type'] = $this->getType();
        $attributes['context'] = $this->getContext();

        return $attributes;
    }

    /**
     * returns a attribute
     * if the attribute is not set, it returns false
     *
     * @param string $name
     *
     * @return mixed
     */
    public function getAttribute($name)
    {
        if (isset($this->attributes[$name])) {
            return $this->attributes[$name];
        }

        return false;
    }

    /**
     * set an attribute
     *
     * @param string $name - name of the attribute
     * @param mixed $val - value of the attribute
     *
     * @return Exception this
     */
    public function setAttribute($name, $val)
    {
        $this->attributes[$name] = $val;

        return $this;
    }

    /**
     * If you want to set more than one attribute
     *
     * @param array $attributes
     *
     * @return Exception
     */
    public function setAttributes($attributes)
    {
        if (!is_array($attributes)) {
            return $this;
        }

        foreach ($attributes as $key => $value) {
            $this->setAttribute($key, $value);
        }

        return $this;
    }
}
