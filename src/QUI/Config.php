<?php

/**
 * This file contains \QUI\Config
 */

namespace QUI;

use QUI;

use function fclose;
use function file_exists;
use function fopen;
use function fwrite;
use function is_array;
use function is_bool;
use function is_numeric;
use function is_string;
use function is_writeable;
use function json_encode;
use function parse_ini_file;
use function str_replace;
use function substr;

/**
 * Class for handling ini files
 *
 * @author  www.pcsg.de (Moritz Scholz)
 * @author  www.pcsg.de (Henning Leutz)
 */
class Config
{
    /**
     * filename
     *
     * @var string
     */
    private $iniFilename = '';

    /**
     * ini entries
     *
     * @var array
     */
    private $iniParsedArray = [];

    /**
     * constructor
     *
     * @param string $filename - (optional) Path to the config
     * @throws QUI\Exception
     */
    public function __construct($filename = '')
    {
        if (!file_exists($filename) && substr($filename, -4) !== '.php') {
            $filename .= '.php';
        }

        if (!file_exists($filename)) {
            return;
        }

        $this->iniFilename    = $filename;
        $this->iniParsedArray = @parse_ini_file($filename, true);

        if ($this->iniParsedArray === false) {
            throw new QUI\Exception('Can\'t parse ini file ' . $filename);
        }
    }

    /**
     * Reload the ini data
     * Read the ini file
     */
    public function reload()
    {
        $this->iniParsedArray = @parse_ini_file($this->iniFilename, true);
    }

    /**
     * Ini entries get as array
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->iniParsedArray;
    }

    /**
     * Return the ini as json encode
     *
     * @return string
     */
    public function toJSON(): string
    {
        return json_encode($this->iniParsedArray);
    }

    /**
     * Returns a complete section
     *
     * @param string $key
     *
     * @return string|array
     */
    public function getSection($key)
    {
        if (!isset($this->iniParsedArray[$key])) {
            return false;
        }

        return $this->iniParsedArray[$key];
    }

    /**
     * Returns a value from a section
     *
     * @param string $section
     * @param string $key
     *
     * @return string|array|boolean
     */
    public function getValue($section, $key)
    {
        if (!isset($this->iniParsedArray[$section])
            || !isset($this->iniParsedArray[$section][$key])
        ) {
            return false;
        }

        return $this->iniParsedArray[$section][$key];
    }

    /**
     * Returns the value of a section or the entire section
     *
     * @param string $section
     * @param string || NULL $key (optional)
     *
     * @return string|array
     */
    public function get($section, $key = null)
    {
        if ($key === null) {
            return $this->getSection($section);
        }

        return $this->getValue($section, $key);
    }

    /**
     * Returns the filename of the config
     *
     * @return string
     */
    public function getFilename(): string
    {
        return $this->iniFilename;
    }

    /**
     * Sets a complete section
     *
     * @param string|boolean $section
     * @param array $array
     *
     * @return boolean
     */
    public function setSection($section = false, $array = [])
    {
        if (!is_array($array)) {
            return false;
        }

        if ($section) {
            $this->iniParsedArray[$section] = $array;

            return true;
        }

        $this->iniParsedArray[] = $array;

        return true;
    }

    /**
     * Sets a new value in a section
     *
     * @param string $section
     * @param string|null $key
     * @param string $value
     *
     * @return boolean
     *
     * @example QConfig->setValue('section', null, 'something');
     * @example QConfig->setValue('section', 'entry', 'something');
     */
    public function setValue($section, $key = null, $value = '')
    {
        if ($key === null) {
            if ($this->iniParsedArray[$section] = $value) {
                return true;
            }
        }

        if ($this->iniParsedArray[$section][$key] = $value) {
            return true;
        }

        return false;
    }

    /**
     * exist the section or value?
     *
     * @param string $section
     * @param string $key - (optional)
     *
     * @return boolean
     */
    public function existValue($section, $key = null): bool
    {
        if ($key === null) {
            return isset($this->iniParsedArray[$section]);
        }

        if (!isset($this->iniParsedArray[$section])) {
            return false;
        }

        return isset($this->iniParsedArray[$section][$key]);
    }

    /**
     * Sets a new value in a section or a whole new section
     *
     * @param string|bool $section - (optional)
     * @param string $key - (optional)
     * @param string $value - (optional)
     *
     * @return mixed
     */
    public function set($section = false, $key = null, $value = null)
    {
        if (is_array($key) && $key === null) {
            return $this->setSection($section, $key);
        }

        return $this->setValue($section, $key, $value);
    }

    /**
     * Deletes a section or key in the section
     *
     * @param string $section
     * @param string $key - optional, If indicated Key deleted otherwise complete section
     *
     * @return boolean
     */
    public function del($section, $key = null): bool
    {
        if (!isset($this->iniParsedArray[$section])) {
            return true;
        }

        if ($key === null) {
            unset($this->iniParsedArray[$section]);

            return true;
        }

        if (isset($this->iniParsedArray[$section][$key])) {
            unset($this->iniParsedArray[$section][$key]);
        }

        if (isset($this->iniParsedArray[$section][$key])) {
            return false;
        }

        return true;
    }

    /**
     * Saves the entries to the INI file
     *
     * @param string $filename - optional, Path to the file
     *
     * @throws Exception
     */
    public function save($filename = null)
    {
        if ($filename === null) {
            $filename = $this->iniFilename;
        }

        if (!is_writeable($filename)) {
            $filename = Utils\Security\Orthos::clear($filename);

            throw new Exception(
                'Config ' . $filename . ' is not writable'
            );
        }

        $FileDescriptor = fopen($filename, "w");

        fwrite($FileDescriptor, ";<?php exit; ?>\n"); // php security

        foreach ($this->iniParsedArray as $section => $array) {
            if (is_array($array)) {
                fwrite($FileDescriptor, "[" . $section . "]\n");

                foreach ($array as $key => $value) {
                    fwrite(
                        $FileDescriptor,
                        $key . '="' . $this->clean($value) . "\"\n"
                    );
                }

                fwrite($FileDescriptor, "\n");
            } else {
                fwrite(
                    $FileDescriptor,
                    $section . '="' . $this->clean($array) . "\"\n"
                );
            }
        }

        fclose($FileDescriptor);
    }

    /**
     * Delete line breaks
     *
     * @param mixed $value
     *
     * @return string
     */
    protected function clean($value): string
    {
        if (is_bool($value)) {
            return $value ? 1 : 0;
        }

        if (is_numeric($value)) {
            return (string)$value;
        }

        if (!is_string($value)) {
            return '';
        }

        $value = str_replace(["\r\n", "\n", "\r"], '', $value);
        $value = str_replace('"', '\"', $value);

        return $value;
    }
}
