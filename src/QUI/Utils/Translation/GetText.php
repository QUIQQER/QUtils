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
class GetText extends QUI\QDOM
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
        $this->setLanguage($lang);
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
        return file_exists($this->getFile());
    }

    /**
     * Return the .mo file path
     * @return string
     */
    public function getFile()
    {
        $locale = $this->getAttribute('locale');
        $dir    = $this->getAttribute('dir');
        $domain = $this->getAttribute('domain');

        return $dir . $locale . '/LC_MESSAGES/' . $domain . '.mo';
    }

    /**
     * Set the locale via language string (en, de)
     *
     * @param string $lang
     */
    public function setLanguage($lang)
    {
        $lower = QUI\Utils\StringHelper::toLower($lang);
        $upper = QUI\Utils\StringHelper::toUpper($lang);

        $this->setAttribute('locale', $lower . '_' . $upper);
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
        $this->set();

        return gettext($key);
    }

    /**
     * Set all the bindings for gettext
     */
    protected function set()
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
