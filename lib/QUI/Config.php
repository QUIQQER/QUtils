<?php

/**
 * This file contains \QUI\Config
 */

namespace QUI;

/**
 * Class for handling ini files
 *
 * @author www.pcsg.de (Moritz Scholz)
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qutils
 *
 * @todo translate the docu
 */

class Config
{
    /**
     * filename
     * @var String
     */
    private $_iniFilename = '';

    /**
     * ini entries
     * @var array
     */
    private $_iniParsedArray = array();

    /**
     * constructor
     *
     * @param String $filename
     * @return Bool
     */
    public function __construct($filename='')
    {
        if ( substr( $filename, -4 ) !== '.php' ) {
            $filename .= '.php';
        }

        if ( !file_exists( $filename ) ) {
            return false;
        }

        $this->_iniFilename = $filename;

        if ( $this->_iniParsedArray = parse_ini_file( $filename, true ) ) {
            return true;
        }

        return false;
    }

    /**
     * Ini Einträge als Array bekommen
     *
     * @return Array
     */
    public function toArray()
    {
        return $this->_iniParsedArray;
    }

    /**
     * Return the ini as json encode
     *
     * @return String
     */
    public function toJSON()
    {
        return json_encode( $this->_iniParsedArray );
    }

    /**
     * Gibt eine komplette Sektion zurück
     *
     * @param String $key
     * @return String || Array
     */
    public function getSection($key)
    {
        if ( !isset( $this->_iniParsedArray[ $key ] ) ) {
            return false;
        }

        return $this->_iniParsedArray[ $key ];
    }

    /**
     * Gibt einen Wert aus einer Sektion zurück
     *
     * @param String $section
     * @param String $key
     * @return String || Array
     */
    public function getValue($section, $key)
    {
        if ( !isset( $this->_iniParsedArray[ $section ] ) ||
             !isset( $this->_iniParsedArray[ $section ][ $key ] ))
        {
            return false;
        }

        return $this->_iniParsedArray[ $section ][ $key ];
    }

    /**
     * Gibt den Wert einer Sektion  oder die ganze Section zurück
     *
     * @param String $section
     * @param String || NULL $key (optional)
     * @return String|Array
     */
    public function get($section, $key=null)
    {
        if ( is_null( $key ) ) {
            return $this->getSection( $section );
        }

        return $this->getValue( $section, $key );
    }

    /**
     * Gibt den Dateinamen der Config zurück
     * @return String
     */
    public function getFilename()
    {
        return $this->_iniFilename;
    }

    /**
     * Setzt eine komplette Sektion
     *
     * @param String|Bool $section
     * @param Array $array
     * @return Bool
     */
    public function setSection($section=false, $array)
    {
        if ( !is_array( $array ) ) {
            return false;
        }

        if ( $section )
        {
            $this->_iniParsedArray[ $section ] = $array;
            return true;
        }

        $this->_iniParsedArray[] = $array;
        return true;
    }

    /**
     * Setzt einen neuen Wert in einer Sektion
     *
     * @param String $section
     * @param String $key
     * @param String $value
     * @return Bool
     *
     * @example QConfig->setValue('section', null, 'something');
     * @example QConfig->setValue('section', 'entry', 'something');
     */
    public function setValue($section, $key=null, $value)
    {
        if ( $key == null )
        {
            if ( $this->_iniParsedArray[ $section ] = $value ) {
                return true;
            }
        }

        if ( $this->_iniParsedArray[ $section ][ $key ] = $value ) {
            return true;
        }

        return false;
    }

    /**
     * exist the section or value?
     *
     * @param String $section
     * @param String $key
     */
    public function existValue($section, $key=null)
    {
        if ( $key === null ) {
            return isset( $this->_iniParsedArray[ $section ] ) ? true : false;
        }

        if ( !isset( $this->_iniParsedArray[ $section ] ) ) {
            return false;
        }

        return isset( $this->_iniParsedArray[ $section ][ $key ] ) ? true : false;
    }

    /**
     * Setzt einen neuen Wert in einer Sektion oder eine gesamte neue Sektion
     *
     * @param String $section
     * @param String $key
     * @param String $value
     * @return Bool
     */
    public function set($section=false, $key=null, $value=null)
    {
        if ( is_array( $key ) && is_null( $value ) ) {
            return $this->setSection( $section, $key );
        }

        return $this->setValue( $section, $key, $value );
    }

    /**
     * Löscht eine Sektion oder ein Key in der Sektion
     *
     * @param String $section
     * @param String $key - optional, wenn angegeben wird Key gelöscht ansonsten komplette Sektion
     * @return Bool
     */
    public function del($section, $key=null)
    {
        if ( !isset( $this->_iniParsedArray[ $section ] ) ) {
            return true;
        }

        if ( is_null( $key ) )
        {
            unset( $this->_iniParsedArray[ $section ] );
            return true;
        }

        if ( isset( $this->_iniParsedArray[ $section ][ $key ] ) ) {
            unset( $this->_iniParsedArray[ $section ][ $key ] );
        }

        if ( isset( $this->_iniParsedArray[ $section ][ $key ] ) ) {
            return false;
        }

        return true;
    }

    /**
     * Speichert die Einträge in die INI Datei
     *
     * @param String $filename - Pfad zur Datei
     * @return Bool
     */
    public function save($filename=null)
    {
        if ( $filename == null ) {
            $filename = $this->_iniFilename;
        }

        if ( !is_writeable( $filename ) )
        {
            $filename = \QUI\Utils\Security\Orthos::clear( $filename );

            throw new \QUI\Exception(
                'Config '. $filename .' is not writable'
            );
        }

        $SFfdescriptor = fopen( $filename, "w" );

        fwrite( $SFfdescriptor, ";<?php exit; ?>\n" ); // php security


        foreach ( $this->_iniParsedArray as $section => $array )
        {
            if ( is_array( $array ) )
            {
                fwrite( $SFfdescriptor, "[". $section ."]\n" );

                foreach ( $array as $key => $value ) {
                    fwrite( $SFfdescriptor, $key .'="'. $this->_clean( $value ) ."\"\n" );
                }

                fwrite( $SFfdescriptor, "\n" );

            } else
            {
                fwrite( $SFfdescriptor, $section .'="'. $this->_clean( $array ) ."\"\n" );
            }
        }

        fclose( $SFfdescriptor );
    }

    /**
     * Zeilenumbrüche löschen
     *
     * @param String $value
     * @return String
     */
    protected function _clean($value)
    {
        $value = str_replace( array("\r\n","\n","\r"), '', $value);
        $value = str_replace( '"', '\"', $value );

        return $value;
    }
}
