<?php

/**
 * This file contains \QUI\Control\Manager
 */

namespace QUI\Control;

/**
 *
 * @author www.pcsg.de (Henning Leutz)
 */

class Manager
{
    /**
     * Lst of CSS Files
     *
     * @var array
     */
    protected static $cssFilesloaded = array();

    /**
     * Return the CSS Files from the loaded Controls
     *
     * @return array
     */
    public static function getCSSFiles()
    {
        return array_keys(self::$cssFilesloaded);
    }

    /**
     * Return the <style></style> of all loaded css files
     *
     * @return string
     */
    public static function getCSS()
    {
        $files  = self::getCSSFiles();
        $result = '<style>';

        foreach ($files as $file) {
            if (!file_exists($file)) {
                continue;
            }

            $css = file_get_contents($file);
            $result .= $css;
        }

        $result .= '</style>';

        return $result;
    }

    /**
     * Add a css file
     *
     * @param string $file - Path to the CSS File, the system file path, no relativ path
     */
    public static function addCSSFile($file)
    {
        self::$cssFilesloaded[$file] = true;
    }

    /**
     * Insert the CSS Files in the <head></head> of a html
     *
     * @param string $html - complete html
     *
     * @return string
     */
    public static function setCSSToHead($html)
    {
        // letzte head ersetzen
        $string  = $html;
        $search  = '</head>';
        $replace = self::getCSS();

        return substr_replace(
            $html,
            $replace . '</head>',
            strrpos($string, $search),
            strlen($search)
        );
    }
}
