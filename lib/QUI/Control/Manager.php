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
     * @var Array
     */
    static protected $cssFilesloaded = array();

    /**
     * Return the CSS Files from the loaded Controls
     *
     * @return Array
     */
    static function getCSSFiles()
    {
        return array_keys( self::$cssFilesloaded );
    }

    /**
     * Return the <style></style> of all loaded css files
     *
     * @return String
     */
    static function getCSS()
    {
        $files = self::getCSSFiles();
        $result = '<style>';

        foreach ( $files as $file )
        {
            if ( !file_exists( $file ) ) {
                continue;
            }

            $css     = file_get_contents( $file );
            $result .= $css;
        }

        $result .= '</style>';

        return $result;
    }

    /**
     * Add a css file
     *
     * @param String $file - Path to the CSS File, the system file path, no relativ path
     */
    static function addCSSFile($file)
    {
        self::$cssFilesloaded[ $file ] = true;
    }

    /**
     * Insert the CSS Files in the <head></head> of a html
     *
     * @param String $html - complete html
     * @return String
     */
    static function setCSSToHead($html)
    {
        // letzte head ersetzen
        $string  = $html;
        $search  = '</head>';
        $replace = self::getCSS();

        return substr_replace(
            $html,
            $replace .'</head>',
            strrpos( $string, $search ),
            strlen( $search )
        );
    }
}
