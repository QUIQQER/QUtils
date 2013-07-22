<?php

/**
 * This file contains the \QUI\Database\Tables
 */

namespace QUI\Database;

/**
 * QUIQQER DataBase Layer for table operations
 *
 * @uses PDO
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qutils
 */

class Tables
{
    /**
     * internal db object
     * @var QUI\Database\DB
     */
    protected $_DB = null;

    /**
     * Konstruktor
     *
     * @param \QUI\Database\DB $DB
     */
    public function __construct(\QUI\Database\DB $DB)
    {
        $this->_DB = $DB;
    }

    /**
     * Is the DB a sqlite db?
     *
     * @return Bool
     */
    protected function _isSQLite()
    {
        return $this->_DB->isSQLite();
    }

    /**
     * Returns all tables in the database
     *
     * @return Array
     */
    public function getTables()
    {
        $tables = array();

        if ( $this->_isSQLite() )
        {
            $result = $this->_DB->getPDO()->query(
                "SELECT name FROM sqlite_master WHERE type='table' ORDER BY name"
            )->fetchAll();

        } else
        {
            $result = $this->_DB->getPDO()->query("SHOW tables")->fetchAll();
        }

        foreach ( $result as $entry )
        {
            if ( isset( $entry[0] ) ) {
                $tables[] = $entry[0];
            }
        }

        return $tables;
    }

    /**
     * Optimiert Tabellen
     *
     * @param String || Array $tables
     */
    public function optimize($tables)
    {
        if ( $this->_isSQLite() ) {
            return;
        }

        if ( is_string( $tables ) ) {
            $tables = array( $tables );
        }

        return $this->_DB->getPDO()->query(
            'OPTIMIZE TABLE `'. implode('`,`', $tables) .'`'
        )->fetchAll();
    }

    /**
     * Prüft ob eine Tabelle existiert
     *
     * @param String $table - Tabellenname welcher gesucht wird
     * @return Bool
     */
    public function exist($table)
    {
        if ( $this->_isSQLite() )
        {
            $data = $this->_DB->getPDO()->query(
                "SELECT name FROM sqlite_master WHERE type='table' AND name ='". $table ."'"
            )->fetchAll();
        } else
        {
            $data = $this->_DB->getPDO()->query(
                'SHOW TABLES FROM `'. $this->_DB->getAttribute('dbname') .'` LIKE "'. $table .'"'
            )->fetchAll();
        }

        return count( $data ) > 0 ? true : false;
    }

    /**
     * Delete a table
     *
     * @param String $table
     */
    public function delete($table)
    {
        if ( !$this->exist( $table ) ) {
            return;
        }

        return $this->_DB->getPDO()->query(
            'DROP TABLE `'. $table .'`'
        );
    }

    /**
     * Execut a TRUNCATE on a table
     * empties a table completely
     *
     * @param String $table
     */
    public function truncate($table)
    {
        if ( !$this->exist( $table ) ) {
            return;
        }

        if ( $this->_isSQLite() )
        {
            $result = $this->_DB->getPDO()->query(
                "SELECT sql FROM sqlite_master
                WHERE tbl_name = '". $table ."' AND type = 'table'"
            )->fetchAll();

            $create = $result[0]['sql'];

            $this->delete( $table );
            $this->_DB->getPDO()->query( $create );

            return;
        }

        $this->_DB->getPDO()->query(
            'TRUNCATE TABLE `'. $table .'`'
        );
    }

    /**
     * Creates a table with the specific fields
     *
     * @param String $table
     * @param Array $fields
     *
     * @return Bool - if table exists or not
     */
    public function create($table, $fields)
    {
        if ( !is_array( $fields ) )
        {
            throw new \QUI\Database\Exception(
                'No Array given \QUI\Database\Tables->createTable'
            );
        }

        if ( $this->_isSQLite() )
        {
            $sql = 'CREATE TABLE `'. $table .'` (';
        } else
        {
            $sql = 'CREATE TABLE `'. $this->_DB->getAttribute('dbname') .'`.`'. $table .'` (';
        }



        if ( \QUI\Utils\ArrayHelper::isAssoc( $fields ) )
        {
            foreach ( $fields as $key => $type ) {
                $sql .= '`'.$key.'` '.$type.',';
            }

            $sql = substr( $sql, 0, -1 );
        } else
        {
            $len = count( $fields );

            for ( $i = 0; $i < $len; $i++ )
            {
                $sql .= $fields[$i];

                if ( $i < $len-1 ) {
                    $sql .= ',';
                }
            }
        }

        if ( $this->_isSQLite() )
        {
            $sql .= ');';
        } else
        {
            $sql .= ') ENGINE = MYISAM DEFAULT CHARSET = utf8;';
        }

        $this->_DB->getPDO()->exec( $sql );

        return $this->exist( $table );
    }

    /**
     * Field Methods
     */

    /**
     * Tabellen Felder
     *
     * @param String $table
     * @return Array
     */
    public function getFields($table)
    {
        $PDO = $this->_DB->getPDO();

        if ( $this->_isSQLite() )
        {
            $result = $PDO->query(
                "SELECT sql FROM sqlite_master
                WHERE tbl_name = '". $table ."' AND type = 'table'"
            )->fetchAll();

            if ( !isset( $result[0] ) && !isset( $result[0] ) ) {
                return array();
            }

            $pos  = mb_strpos( $result[0]['sql'], '(' );
            $data = mb_substr( $result[0]['sql'], $pos+1 );

            $fields = array();
            $expl    = explode( ',', $data );

            foreach ( $expl as $part )
            {
                $part = str_replace( '"', '', $part );

                if ( strpos( $part, '`' ) !== false )
                {
                    preg_match("/`(.*?)`/", $part, $matches);

                    if ( isset( $matches[1] ) ) {
                        $part = $matches[1];
                    }
                }

                $fields[] = $part;
            }

            return $fields;
        }


        $Stmnt  = $PDO->query( "SHOW COLUMNS FROM `" . $table ."`" );
        $result = $Stmnt->fetchAll( \PDO::FETCH_ASSOC );
        $fields = array();

        foreach ( $result as $entry ) {
            $fields[] = $entry['Field'];
        }

        return $fields;
    }

    /**
     * Erweitert Tabellen mit den Feldern
     * Wenn die Tabelle nicht existiert wird diese erstellt
     *
     * @param String $table
     * @param Array $fields
     */
    public function appendFields($table, $fields)
    {
        if ( $this->exist( $table ) == false )
        {
            $this->create( $table, $fields );
            return;
        }

        $tbl_fields = $this->getFields( $table );

        foreach ( $fields as $field => $type )
        {
            if ( !in_array( $field, $tbl_fields ) )
            {
                if ( $this->_isSQLite() )
                {
                    $this->_DB->getPDO()->exec(
                        'ALTER TABLE "'. $table .'" ADD COLUMN `'. $field .'` '. strtoupper($type) .';'
                    );

                    continue;
                }

                $this->_DB->getPDO()->exec(
                    'ALTER TABLE `'. $table .'` ADD `'. $field .'` '. $type .';'
                );
            }
        }
    }

    /**
     * Löscht ein Feld / Spalte aus der Tabelle
     *
     * @param unknown_type $table
     * @param unknown_type $fields
     */
    public function deleteFields($table, $fields)
    {
        $table = \QUI\Utils\Security\Orthos::clearMySQL( $table );

        if ( $this->exist( $table ) == false) {
            return true;
        }

        $tbl_fields   = $this->getFields( $table );
        $table_fields = \QUI\Utils\ArrayHelper::toAssoc( $tbl_fields );

        // prüfen ob die Tabelle leer wäre wenn alle Felder gelöscht werden
        // wenn ja, Tabelle löschen
        foreach ( $fields as $field => $type )
        {
            if ( isset( $table_fields[ $field ] ) ) {
                unset( $table_fields[ $field ] );
            }
        }

        if ( empty( $table_fields ) )
        {
            $this->delete( $table );
            return;
        }


        // Einzeln die Felder löschen
        foreach ( $fields as $field => $type )
        {
            if ( in_array( $field, $tbl_fields ) ) {
                $this->deleteColum( $table, $field );
            }
        }
    }

    /**
     * Column Methods
     */

    /**
     * Prüft ob eine Spalte in der Tabelle existiert
     *
     * @param unknown_type $table
     * @param unknown_type $row
     *
     * @return Bool
     */
    public function existColumnInTable($table, $row)
    {
        if ( $this->_isSQLite() == false )
        {
            $data = $this->_DB->getPDO()->query(
                'SHOW COLUMNS FROM `'. $table .'` LIKE "'. $row .'"'
            )->fetchAll();

            return count($data) > 0 ? true : false;
        }


        // sqlite part
        $columns = $this->getFields( $table );

        foreach ( $columns as $col )
        {
            if ( $col == $row ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Alle Spalten der Tabelle bekommen
     *
     * @param String $table
     * @return Array
     */
    public function getColumns($table)
    {
        if ( $this->_isSQLite() ) {
            return $this->getFields( $table );
        }

        return $this->_DB->getPDO()->query(
            'SHOW COLUMNS FROM `'. $table .'`'
        )->fetchAll();
    }

    /**
     * Return the informations of a column
     *
     * @param String $table - Table name
     * @param String $column - Row name
     */
    public function getColumn($table, $column)
    {
        if ( $this->_isSQLite() )
        {
            $result =  $this->_DB->getPDO()->query(
                'PRAGMA table_info(`'. $table .'`);'
            )->fetch();


            var_dump($result['name']);

        }

        return $this->_DB->getPDO()->query(
            'SHOW COLUMNS FROM `'. $table .'` LIKE "'. $column .'"'
        )->fetch();
    }

    /**
     * Löscht eine Spalte aus der Tabelle
     *
     * @param unknown_type $table
     * @param unknown_type $row
     */
    public function deleteColumn($table, $row)
    {
        $table = \QUI\Utils\Security\Orthos::clearMySQL( $table );
        $row   = \QUI\Utils\Security\Orthos::clearMySQL( $row );

        if ( !$this->existColumnInTable( $table, $row ) ) {
            return;
        }

        $data = $this->_DB->getPDO()->query(
            'ALTER TABLE `'. $table .'` DROP `'. $row .'`'
        )->fetch();

        return $data ? true : false;
    }

    /**
     * Key Methods
     */

    /**
     * Schlüssel der Tabelle bekommen
     *
     * @param unknown_type $table
     * @return Array
     */
    public function getKeys($table)
    {
        if ( $this->_isSQLite() ) {
            return array();
        }

        return $this->_DB->getPDO()->query(
            'SHOW KEYS FROM `'. $table .'`'
        )->fetchAll();
    }

    /**
     * Prüft ob der PrimaryKey gesetzt ist
     *
     * @param String $table
     * @param String || Array $key
     *
     * @return Bool
     */
    public function issetPrimaryKey($table, $key)
    {
        if ( is_array( $key ) )
        {
            foreach ( $key as $entry )
            {
                if ( $this->_issetPrimaryKey( $table, $entry  == false ) ) {
                    return false;
                }
            }

            return true;
        }

        return $this->_issetPrimaryKey($table, $key);
    }

    /**
     * Helper for issetPrimaryKey
     * @see issetPrimaryKey
     *
     * @param String $table
     * @param String $key
     *
     * @return Bool
     */
    protected function _issetPrimaryKey($table, $key)
    {
        $keys = $this->getKeys( $table );

        foreach ( $keys as $entry )
        {
            if ( isset($entry['Column_name'] ) &&
                $entry['Column_name'] == $key)
            {
                return true;
            }
        }

        return false;
    }

    /**
     * Setzt ein PrimaryKey einer Tabelle
     *
     * @param String $table
     * @param String|Array $key
     *
     * @return Bool
     */
    public function setPrimaryKey($table, $key)
    {
        // You can't modify SQLite tables in any significant way after they have been created
        if ( $this->_isSQLite() ) {
            return true;
        }


        if ( $this->issetPrimaryKey( $table, $key ) ) {
            return true;
        }

        $k = $key;

        if ( is_array( $key ) )
        {
            $k = "`". implode("`,`", $key) ."`";
        } else
        {
            $k = "`". $key ."`";
        }

        $this->_DB->getPDO()->exec(
            'ALTER TABLE `'. $table .'` ADD PRIMARY KEY('. $k .')'
        );

        return $this->issetPrimaryKey( $table, $key );
    }

    /**
     * Index Methods
     */

    /**
     * Prüft ob ein Index gesetzt ist
     *
     * @param unknown_type $table
     * @param String | Integer $key
     *
     * @return Bool
     */
    public function issetIndex($table, $key)
    {
        if ( is_array( $key ) )
        {
            foreach ( $key as $entry )
            {
                if ( $this->_issetIndex( $table, $entry ) == false ) {
                    return false;
                }
            }

            return true;
        }

        return $this->_issetIndex( $table, $key );
    }

    /**
     * Prüft ob ein Index gesetzt ist -> subroutine
     *
     * @param String $table
     * @param String $key
     *
     * @return Bool
     */
    protected function _issetIndex($table, $key)
    {
        $i = $this->getIndex( $table );

        if ( !$i || empty( $i ) ) {
            return false;
        }

        if ( $this->_isSQLite() )
        {
            foreach ( $i as $key => $value )
            {
                if ( $value ==  $key ) {
                    return true;
                }
            }

            return false;
        }

        foreach ( $i as $entry )
        {
            if ( isset( $entry['Column_name'] ) &&
                 $entry['Column_name'] == $key)
            {
                return true;
            }
        }

        return false;
    }

    /**
     * Liefert die Indexes einer Tabelle
     *
     * @param String $table
     * @return unknown
     */
    public function getIndex($table)
    {
        if ( $this->_isSQLite() )
        {
            try
            {
                $result = $this->_DB->getPDO()->query(
                    "SELECT * FROM sqlite_master WHERE type = 'index'"
                )->fetch();

            } catch ( \PDOException $Exception )
            {
                return array();
            }

            return $result;
        }

        return $this->_DB->getPDO()->query(
            'SHOW INDEX FROM `'. $table .'`'
        )->fetchAll();
    }

    /**
     * Setzt einen Index
     *
     * @param String $table
     * @param String || Array $index - Array not working on SQLite
     *
     * @return Bool
     */
    public function setIndex($table, $index)
    {
        if ( $this->issetIndex( $table, $index ) ) {
            return true;
        }

        $in = $index;

        if ( is_array( $index ) )
        {
            $in = "`". implode( "`,`", $index ) ."`";
        } else
        {
             $in = "`". $index ."`";
        }

        if ( $this->_isSQLite() )
        {
            $this->_DB->getPDO()->exec(
                'CREATE INDEX '. $in .' ON '. $table .' ('. $in .')'
            );

            return $this->issetIndex( $table, $index );
        }

        $this->_DB->getPDO()->exec(
            'ALTER TABLE `'. $table .'` ADD INDEX('. $in .')'
        );

        return $this->issetIndex( $table, $index );
    }

    /**
     * Set the Autoincrement to the column
     *
     * @param String $table
     * @param String $index
     */
    public function setAutoIncrement($table, $index)
    {
        if ( $this->_isSQLite() )
        {
            throw new \QUI\Exception(
                'You can\'t modify SQLite tables in any significant way after they have been created in SQLite'
            );
        }

        $column = $this->getColumn( $table, $index );

        if ( !$this->issetIndex($table, $index) ) {
            $this->setIndex( $table, $index );
        }

        $query  = 'ALTER TABLE `'. $table .'`';
        $query .= 'MODIFY COLUMN `'. $index .'`';

        $query .= ' '. $column['Type'];

        if ( $column['Null'] === 'No' )
        {
            $query .= ' NOT NULL';
        } else
        {
            $query .= ' NULL';
        }

        $query .= ' AUTO_INCREMENT';

        $this->_DB->getPDO()->exec( $query );
    }

    /**
     * Fulltext Methods
     */

    /**
     * Setzt einen Fulltext
     *
     * @param String $table
     * @param String || Array $index
     *
     * @return Bool
     */
    public function setFulltext($table, $index)
    {
        // no fulltext in sqlite
        if ( $this->_isSQLite() ) {
            throw new \QUI\Exception( 'Use USING fts4 for SQLite' );
        }

        if ( $this->issetFulltext( $table, $index ) ) {
            return true;
        }

        $in = $index;

        if ( is_array( $index ) )
        {
            $in = "`". implode("`,`", $index) ."`";
        } else
        {
             $in = "`". $index ."`";
        }

        $this->_DB->getPDO()->exec(
            'ALTER TABLE `'. $table .'` ADD FULLTEXT('. $in .')'
        );

        return $this->issetFulltext( $table, $index );
    }

    /**
     * Prüft ob ein Fulltext auf das Feld gesetzt ist
     *
     * @param String $table
     * @param String|Integer $key
     *
     * @return Bool
     */
    public function issetFulltext($table, $key)
    {
        if ( $this->_isSQLite() ) {
            throw new \QUI\Exception( 'Use USING fts4 for SQLite' );
        }

        if ( is_array( $key ) )
        {
            foreach ( $key as $entry )
            {
                if ( $this->_issetFulltext( $table, $entry ) == false ) {
                    return false;
                }
            }

            return true;
        }

        return $this->_issetFulltext( $table, $key );
    }

    /**
     * Prüft ob ein Fulltext auf das Feld gesetzt ist -> subroutine
     *
     * @param String $table
     * @param String $key
     */
    protected function _issetFulltext($table, $key)
    {
        $keys = $this->getKeys( $table );

        foreach ( $keys as $entry )
        {
            if ( isset($entry['Column_name']) &&
                 isset($entry['Index_type']) &&
                 $entry['Column_name'] == $key &&
                 $entry['Index_type'] == 'FULLTEXT' )
            {
                return true;
            }
        }

        return false;
    }
}
