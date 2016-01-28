<?php

/**
 * This file contains \QUI\Config
 */

namespace QUI;

/**
 * Class for handling ini files
 *
 * @author  www.pcsg.de (Moritz Scholz)
 * @author  www.pcsg.de (Henning Leutz)
 * @package quiqqer/utils
 *
 * @todo    translate the docu
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
    private $iniParsedArray = array();

    /**
     * constructor
     *
     * @param string $filename - (optional) Path to the config
     */
    public function __construct($filename = '')
    {
        if (substr($filename, -4) !== '.php') {
            $filename .= '.php';
        }

        if (!file_exists($filename)) {
            return;
        }

        $this->iniFilename = $filename;

        if ($this->iniParsedArray = @parse_ini_file($filename, true)) {
            return;
        }

        return;
    }

    /**
     * Ini Einträge als Array bekommen
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
        return json_encode($this->iniParsedArray);
    }

    /**
     * Gibt eine komplette Sektion zurück
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
     * Gibt einen Wert aus einer Sektion zurück
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
     * Gibt den Wert einer Sektion  oder die ganze Section zurück
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
     * Gibt den Dateinamen der Config zurück
     *
     * @return string
     */
    public function getFilename()
    {
        return $this->iniFilename;
    }

    /**
     * Setzt eine komplette Sektion
     *
     * @param string|boolean $section
     * @param array $array
     *
     * @return boolean
     */
    public function setSection($section = false, $array = array())
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
     * Setzt einen neuen Wert in einer Sektion
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
     * Setzt einen neuen Wert in einer Sektion oder eine gesamte neue Sektion
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
     * Löscht eine Sektion oder ein Key in der Sektion
     *
     * @param string $section
     * @param string $key - optional, wenn angegeben wird Key gelöscht ansonsten komplette Sektion
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
     * Speichert die Einträge in die INI Datei
     *
     * @param string $filename - (optional) Pfad zur Datei
     *
     * @return boolean
     * @throws \QUI\Exception
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

        $SFfdescriptor = fopen($filename, "w");

        fwrite($SFfdescriptor, ";<?php exit; ?>\n"); // php security

        foreach ($this->iniParsedArray as $section => $array) {
            if (is_array($array)) {
                fwrite($SFfdescriptor, "[" . $section . "]\n");

                foreach ($array as $key => $value) {
                    fwrite(
                        $SFfdescriptor,
                        $key . '="' . $this->clean($value) . "\"\n"
                    );
                }

                fwrite($SFfdescriptor, "\n");

            } else {
                fwrite(
                    $SFfdescriptor,
                    $section . '="' . $this->clean($array) . "\"\n"
                );
            }
        }

        fclose($SFfdescriptor);
    }

    /**
     * Zeilenumbrüche löschen
     *
     * @param string $value
     *
     * @return string
     */
    protected function clean($value)
    {
        $value = str_replace(array("\r\n", "\n", "\r"), '', $value);
        $value = str_replace('"', '\"', $value);

        return $value;
    }
}
