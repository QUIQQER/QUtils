<?php

namespace QUI\Utils\System;

use function fgets;
use function system;
use function trim;

class Console
{
    const COLOR_GREEN = '1;32';
    const COLOR_CYAN = '1;36';
    const COLOR_RED = '1;31';
    const COLOR_YELLOW = '1;33';
    const COLOR_PURPLE = '1;35';
    const COLOR_WHITE = '1;37';
    const COLOR_GREY = '0;37';
    const COLOR_BLACK = '0;30';

    const BACKGROUND_GREEN = '1;42';
    const BACKGROUND_CYAN = '1;46';
    const BACKGROUND_RED = '1;41';
    const BACKGROUND_YELLOW = '1;43';
    const BACKGROUND_PURPLE = '1;45';
    const BACKGROUND_WHITE = '1;47';
    const BACKGROUND_GREY = '0;47';
    const BACKGROUND_BLACK = '0;40';

    /**
     * Prompts the user for Input
     * @return string
     */
    public static function read()
    {
        return trim(fgets(STDIN));
    }

    /**
     * Prompts the user for Input.
     * Hides the input on the console.
     * @return string
     */
    public static function readPassword(): string
    {
        system('stty -echo');
        $result = trim(fgets(STDIN));
        system('stty echo');

        return $result;
    }

    /**
     * Prints a line on the console.
     * @param $msg
     */
    public static function writeLn($msg)
    {
        echo $msg . PHP_EOL;
    }

    /**
     * Prints the message on the console.
     * @param $msg
     */
    public static function write($msg)
    {
        echo $msg;
    }

    /**
     * Gets the colored version of the given String
     * Colorcodes are available as Contants
     *
     * @param $text - The base string
     * @param $color - The colorcode which should be applied
     * @param bool $background - (optional) The colorcode for the background.
     * @return string The color encoded string
     * @see Console::COLOR_YELLOW
     * @see Console::BACKGROUND_BLACK
     * @see https://en.wikipedia.org/wiki/ANSI_escape_code
     */
    public static function getColoredString($text, $color, bool $background = false): string
    {
        if ($background !== false) {
            return "\033[" . $color . ";" . $background . "m" . $text . "\033[0m";
        }

        return "\033[" . $color . "m" . $text . "\033[0m";
    }
}
