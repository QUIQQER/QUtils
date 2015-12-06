<?php

/**
 * This file contains \QUI\Utils\Translation\GetText
 */

namespace QUI\Utils\Translation;

use QUI;

/**
 * Bridge for gettext
 *
 * Easier access to gettext for QUIQQER
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.utils.translation
 *
 * @uses    gettext
 */

class GetText extends \QUI\QDOM
{
    /**
     * Constructor
     *
     * @param string $lang - Sprache
     * @param string $domain - Domain, Gruppe
     * @param string $dir - Folder
     */
    public function __construct($lang, $domain, $dir)
    {
        $this->setAttribute(
            'locale',
            QUI\Utils\String::toLower($lang) . '_'
            . QUI\Utils\String::toUpper($lang)
        );

        $this->setAttribute('domain', str_replace('/', '_', $domain));
        $this->setAttribute('dir', $dir);
    }

    /**
     * Exist the translation file?
     *
     * @return boolean
     */
    public function fileExist()
    {
        return file_exists(
            $this->getAttribute('dir') . 'de_DE/LC_MESSAGES/'
            . $this->getAttribute('domain') . '.mo'
        );
    }

    /**
     * Get the translation
     *
     * @param string $key
     *
     * @return string
     */
    public function get($key)
    {
        $this->_set();

        return gettext($key);
    }

    /**
     * Set all the bindings for gettext
     */
    protected function _set()
    {
        //@todo Ganzes System auf die Aktuelle Sprache inkl. Dezimal etc..

        /*
        setlocale(
            6,
            $this->getAttribute('locale') .".UTF-8",
            $this->getAttribute('locale') .".utf8",
            $this->getAttribute('locale') .".UTF8",
            $this->getAttribute('locale') .".utf-8",
            $this->getAttribute('locale')
        );
        */

        bindtextdomain(
            $this->getAttribute('domain'),
            $this->getAttribute('dir')
        );
        bind_textdomain_codeset($this->getAttribute('domain'), 'UTF-8');

        textdomain($this->getAttribute('domain'));
    }
}