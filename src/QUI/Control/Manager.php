<?php

/**
 * This file contains \QUI\Control\Manager
 */

namespace QUI\Control;

use function array_keys;
use function file_exists;
use function file_get_contents;
use function strlen;
use function strrpos;
use function substr_replace;

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
    protected static array $cssFilesLoaded = [];

    /**
     * Return the CSS Files from the loaded Controls
     *
     * @return array
     */
    public static function getCSSFiles(): array
    {
        return array_keys(self::$cssFilesLoaded);
    }

    /**
     * Return the <style></style> of all loaded css files
     *
     * @return string
     */
    public static function getCSS(): string
    {
        $files = self::getCSSFiles();
        $result = '';

        foreach ($files as $file) {
            if (!file_exists($file)) {
                continue;
            }

            $css = file_get_contents($file);

            $result .= '<style>';
            $result .= $css;
            $result .= '</style>';
        }

        return $result;
    }

    /**
     * Add a css file
     *
     * @param string $file - Path to the CSS File, the system file path, no relative path
     */
    public static function addCSSFile(string $file): void
    {
        self::$cssFilesLoaded[$file] = true;
    }

    /**
     * Insert the CSS Files in the <head></head> of a html
     *
     * @param string $html - complete html
     *
     * @return string
     */
    public static function setCSSToHead(string $html): string
    {
        $string = $html;
        $search = '</head>';
        $replace = self::getCSS();

        return substr_replace(
            $html,
            $replace . '</head>',
            strrpos($string, $search),
            strlen($search)
        );
    }
}
