<?php

/**
 * This file contains \QUI\Config
 */

namespace QUI;

use QUI;

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
        if (!\file_exists($filename) && \substr($filename, -4) !== '.php') {
            $filename .= '.php';
        }

        if (!\file_exists($filename)) {
            return;
        }

        $this->iniFilename    = $filename;
        $this->iniParsedArray = @\parse_ini_file($filename, true);

        if ($this->iniParsedArray === false) {
            throw new QUI\Exception('Can\'t parse ini file '.$filename);
        }
    }

    /**
     * Reload the ini data
     * Read the ini file
     */
    public function reload()
    {
        $this->iniParsedArray = @\parse_ini_file($this->iniFilename, true);
    }

    /**
     * Ini entries get as array
     *
     * @return array
     */
    public function toArray()
    {
        return $this->iniParsedArray;
    }

    /**
     * Return the ini as json encode
     *
     * @return string
     */
    public function toJSON()
    {
        return \json_encode($this->iniParsedArray);
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
    public function getFilename()
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
        if (!\is_array($array)) {
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
    public function existValue($section, $key = null)
    {
        if ($key === null) {
            return isset($this->iniParsedArray[$section]) ? true : false;
        }

        if (!isset($this->iniParsedArray[$section])) {
            return false;
        }

        return isset($this->iniParsedArray[$section][$key]) ? true : false;
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
        if (\is_array($key) && $key === null) {
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
    public function del($section, $key = null)
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
     * @throws \QUI\Exception
     */
    public function save($filename = null)
    {
        if ($filename === null) {
            $filename = $this->iniFilename;
        }

        if (!\is_writeable($filename)) {
            $filename = Utils\Security\Orthos::clear($filename);

            throw new Exception(
                'Config '.$filename.' is not writable'
            );
        }

        $SFfdescriptor = \fopen($filename, "w");

        \fwrite($SFfdescriptor, ";<?php exit; ?>\n"); // php security

        foreach ($this->iniParsedArray as $section => $array) {
            if (\is_array($array)) {
                \fwrite($SFfdescriptor, "[".$section."]\n");

                foreach ($array as $key => $value) {
                    \fwrite(
                        $SFfdescriptor,
                        $key.'="'.$this->clean($value)."\"\n"
                    );
                }

                \fwrite($SFfdescriptor, "\n");
            } else {
                \fwrite(
                    $SFfdescriptor,
                    $section.'="'.$this->clean($array)."\"\n"
                );
            }
        }

        \fclose($SFfdescriptor);
    }

    /**
     * Delete line breaks
     *
     * @param string $value
     *
     * @return string
     */
    protected function clean($value)
    {
        $value = \str_replace(["\r\n", "\n", "\r"], '', $value);
        $value = \str_replace('"', '\"', $value);

        return $value;
    }
}
