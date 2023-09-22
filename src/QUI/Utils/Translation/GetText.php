<?php

/**
 * This file contains \QUI\Utils\Translation\GetText
 */

namespace QUI\Utils\Translation;

use QUI;

use function bind_textdomain_codeset;
use function bindtextdomain;
use function file_exists;
use function mb_strtolower;
use function mb_strtoupper;
use function str_replace;
use function strlen;
use function textdomain;
use function trim;

/**
 * Bridge for gettext
 *
 * Easier access to gettext for QUIQQER
 *
 * @author  www.pcsg.de (Henning Leutz)
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
        $locale = trim($this->getAttribute('locale'));
        $dir = trim($this->getAttribute('dir'));
        $domain = trim($this->getAttribute('domain'));

        return $dir . $locale . '/LC_MESSAGES/' . $domain . '.mo';
    }

    /**
     * Set the locale via language string (en, de)
     *
     * @param string $lang
     */
    public function setLanguage($lang)
    {
        if (strlen($lang) == 2) {
            $lower = mb_strtolower($lang);
            $upper = mb_strtoupper($lang);

            $this->setAttribute('locale', $lower . '_' . $upper);

            return;
        }

        $this->setAttribute('locale', $lang);
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

        return \gettext($key);
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

        if (empty($this->getAttribute('domain'))) {
            QUI\System\Log::addWarning('Missing locale domain', [
                'domain' => $this->getAttribute('domain'),
                'dir' => $this->getAttribute('dir'),
            ]);

            return;
        }

        bindtextdomain(
            $this->getAttribute('domain'),
            $this->getAttribute('dir')
        );
        bind_textdomain_codeset($this->getAttribute('domain'), 'UTF-8');

        textdomain($this->getAttribute('domain'));
    }
}
