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
    protected array $attributes = [];

    /**
     * context data
     *
     * @var array
     */
    protected array $context = [];

    /**
     * Constructor
     *
     * @param string|array $message - Text der Exception
     * @param integer $code - Error code der Exception
     * @param array $context - [optional] Context data, which data
     */
    public function __construct($message = null, int $code = 0, $context = [])
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

        parent::__construct((string)$message, $code);

        if (!empty($context)) {
            $this->context = $context;
        }
    }

    /**
     * Return the Exception type
     *
     * @return string
     */
    public function getType(): string
    {
        return get_class($this);
    }

    /**
     * Return the context data
     *
     * @return array
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Return the Exception as an array
     *
     * @return array
     */
    public function toArray(): array
    {
        $attributes = $this->attributes;

        $attributes['code'] = $this->getCode();
        $attributes['message'] = $this->getMessage();
        $attributes['type'] = $this->getType();
        $attributes['context'] = $this->getContext();

        return $attributes;
    }

    /**
     * returns an attribute
     * if the attribute is not set, it returns false
     *
     * @param string $name
     *
     * @return mixed
     */
    public function getAttribute(string $name): mixed
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
     * @param mixed $value - value of the attribute
     */
    public function setAttribute(string $name, mixed $value): void
    {
        $this->attributes[$name] = $value;
    }

    /**
     * If you want to set more than one attribute
     *
     * @param array $attributes
     * @return void
     */
    public function setAttributes(array $attributes): void
    {
        foreach ($attributes as $key => $value) {
            $this->setAttribute($key, $value);
        }
    }
}
