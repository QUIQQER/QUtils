<?php

/**
 * This file contains the \QUI\Database\Tables
 */

namespace QUI\Database;

use QUI;

/**
 * QUIQQER DataBase Layer for table operations
 *
 * @uses    PDO
 * @author  www.pcsg.de (Henning Leutz)
 * @package quiqqer/utils
 */
class Tables
{
    /**
     * internal db object
     *
     * @var \QUI\Database\DB
     */
    protected $_DB = null;

    /**
     * Konstruktor
     *
     * @param \QUI\Database\DB $DB
     */
    public function __construct(DB $DB)
    {
        $this->_DB = $DB;
    }

    /**
     * Is the DB a sqlite db?
     *
     * @return boolean
     */
    protected function _isSQLite()
    {
        return $this->_DB->isSQLite();
    }

    /**
     * Returns all tables in the database
     *
     * @return array
     */
    public function getTables()
    {
        $tables = array();
        $PDO    = $this->_DB->getPDO();

        if ($this->_isSQLite()) {
            $result = $PDO->query(
                "SELECT name FROM sqlite_master WHERE type='table' ORDER BY name"
            )->fetchAll();

        } else {
            $result = $PDO->query("SHOW tables")->fetchAll();
        }

        foreach ($result as $entry) {
            if (isset($entry[0])) {
                $tables[] = $entry[0];
            }
        }

        return $tables;
    }

    /**
     * Optimiert Tabellen
     *
     * @param string|array $tables
     *
     * @return void
     */
    public function optimize($tables)
    {
        if ($this->_isSQLite()) {
            return;
        }

        $inList = $this->_inList($tables);

        $PDO = $this->_DB->getPDO();
        $PDO->prepare("OPTIMIZE TABLE {$inList}")->execute();
    }

    /**
     * Exist the table?
     *
     * @param string $table - Tabellenname welcher gesucht wird
     *
     * @return boolean
     */
    public function exist($table)
    {
        $PDO   = $this->_DB->getPDO();
        $table = $this->_clear($table);

        if ($this->_isSQLite()) {
            $Stmnt = $PDO->prepare(
                "SELECT name FROM sqlite_master WHERE type='table' AND name = :table"
            );

        } else {
            $dbname = $this->_DB->getAttribute('dbname');

            $Stmnt = $PDO->prepare(
                "SHOW TABLES FROM `{$dbname}` LIKE :table"
            );
        }

        $Stmnt->bindParam(':table', $table, \PDO::PARAM_STR);
        $Stmnt->execute();

        $data = $Stmnt->fetchAll();

        return count($data) > 0 ? true : false;
    }

    /**
     * Delete a table
     *
     * @param string $table
     *
     * @return void
     */
    public function delete($table)
    {
        if (!$this->exist($table)) {
            return;
        }

        $table = $this->_clear($table);
        $PDO   = $this->_DB->getPDO();

        $PDO->prepare("DROP TABLE `{$table}`")->execute();
    }

    /**
     * Execut a TRUNCATE on a table
     * empties a table completely
     *
     * @param string $table
     */
    public function truncate($table)
    {
        if (!$this->exist($table)) {
            return;
        }

        $PDO = $this->_DB->getPDO();

        $queryTable = $this->_clear($table);

        if ($this->_isSQLite()) {
            $Stmnt = $PDO->prepare(
                "SELECT sql FROM sqlite_master
                WHERE tbl_name = '{$queryTable}' AND type = 'table'"
            );

            $Stmnt->execute();

            $result = $Stmnt->fetchAll();
            $create = $result[0]['sql'];

            $this->delete($table);
            $PDO->query($create);

            return;
        }

        $PDO->prepare("TRUNCATE TABLE `{$queryTable}`")->execute();
    }

    /**
     * Creates a table with the specific fields
     *
     * @param string $table
     * @param array $fields
     * @param string $engine
     *
     * @return boolean - if table exists or not
     * @throws QUI\Database\Exception
     * @todo check mysql injection
     */
    public function create($table, $fields, $engine = 'MYISAM')
    {
        if (!is_array($fields)) {
            throw new QUI\Database\Exception(
                'No Array given \QUI\Database\Tables->createTable'
            );
        }

        $_table = $this->_clear($table);

        switch ($engine) {
            case 'InnoDB':
            case 'MYISAM':
            case 'Memory':
            case 'Merge':
            case 'Archive':
            case 'Federated':
            case 'NDB':
            case 'CSV':
            case 'Blackhole':
            case 'Example':
                break;

            default:
                $engine = 'MYISAM';
                break;
        }

        if ($this->_isSQLite()) {
            $sql = 'CREATE TABLE `' . $_table . '` (';
        } else {
            $sql = 'CREATE TABLE `' . $this->_DB->getAttribute('dbname') . '`.`'
                . $_table . '` (';
        }


        if (QUI\Utils\ArrayHelper::isAssoc($fields)) {
            foreach ($fields as $key => $type) {
                $sql .= '`' . $key . '` ' . $type . ',';
            }

            $sql = substr($sql, 0, -1);
        } else {
            $len = count($fields);

            for ($i = 0; $i < $len; $i++) {
                $sql .= $fields[$i];

                if ($i < $len - 1) {
                    $sql .= ',';
                }
            }
        }

        if ($this->_isSQLite()) {
            $sql .= ');';
        } else {
            $sql .= ') ENGINE = ' . $engine . ' DEFAULT CHARSET = utf8;';
        }

        $this->_DB->getPDO()->exec($sql);

        return $this->exist($table);
    }

    /**
     * Field Methods
     */

    /**
     * Tabellen-Spalten mit weiterführenden Informationen
     *
     * @param string $table
     *
     * @return array
     */
    public function getFields($table)
    {
        $PDO   = $this->_DB->getPDO();
        $table = $this->_clear($table);

        if ($this->_isSQLite()) {
            $fields = array();

            $Stmt = $PDO->prepare("PRAGMA table_info(`{$table}`)");
            $Stmt->execute();
            $result = $Stmt->fetchAll();

            foreach ($result as $k => $row) {
                $fields[] = $row['name'];
            }

            return $fields;

//            $Stmnt = $PDO->prepare(
//                "SELECT sql FROM sqlite_master
//                WHERE tbl_name = `{$table}` AND type = 'table'"
//            );
//
//            $Stmnt->execute();
//
//            $result = $Stmnt->fetchAll();
//
//            if ( !isset( $result[0] ) && !isset( $result[0] ) ) {
//                return array();
//            }
//
//            $pos  = mb_strpos( $result[0]['sql'], '(' );
//            $data = mb_substr( $result[0]['sql'], $pos+1 );
//
//            $fields = array();
//            $expl    = explode( ',', $data );
//
//            foreach ( $expl as $part )
//            {
//                $part = str_replace( '"', '', $part );
//
//                if ( strpos( $part, '`' ) !== false )
//                {
//                    preg_match("/`(.*?)`/", $part, $matches);
//
//                    if ( isset( $matches[1] ) ) {
//                        $part = $matches[1];
//                    }
//                }
//
//                $fields[] = $part;
//            }
//
//            return $fields;
        }

        $Stmnt = $PDO->prepare("SHOW COLUMNS FROM `{$table}`");
        $Stmnt->execute();

        $result = $Stmnt->fetchAll(\PDO::FETCH_ASSOC);
        $fields = array();

        foreach ($result as $entry) {
            $fields[] = $entry['Field'];
        }

        return $fields;
    }

    /**
     * Tabellen-Spalten mit detailliertern Spalten-Informationen
     *
     * @param string $table - Tabelle
     *
     * @return array
     */
    public function getFieldsInfos($table)
    {
        $PDO   = $this->_DB->getPDO();
        $table = $this->_clear($table);

        if ($this->_isSQLite()) {
            $Stmnt = $PDO->prepare("PRAGMA table_info(`{$table}`)");
        } else {
            $Stmnt = $PDO->prepare("SHOW COLUMNS FROM `{$table}`");
        }

        $Stmnt->execute();

        return $Stmnt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Erweitert Tabellen mit den Feldern
     * Wenn die Tabelle nicht existiert wird diese erstellt
     *
     * @param string $table
     * @param array $fields
     * @param string $engine - optional, is only used when the table is created
     */
    public function appendFields($table, $fields, $engine = 'MYISAM')
    {
        if ($this->exist($table) == false) {
            $this->create($table, $fields, $engine);

            return;
        }

        $tblFields = $this->getFields($table);

        $PDO   = $this->_DB->getPDO();
        $table = $this->_clear($table);

        foreach ($fields as $field => $type) {
            $field = $this->_clear($field);
            $type  = $this->_parseFieldType($type);

            if (!in_array($field, $tblFields)) {
                if ($this->_isSQLite()) {
                    $Stmnt
                        = $PDO->prepare("ALTER TABLE `{$table}` ADD COLUMN `{$field}` {$type}");
                    $Stmnt->execute();

                    continue;
                }

                $Stmnt
                    = $PDO->prepare("ALTER TABLE `{$table}` ADD `{$field}` {$type}");
                $Stmnt->execute();
            }
        }
    }

    /**
     * Löscht ein Feld / Spalte aus der Tabelle
     *
     * @param string $table
     * @param array $fields
     *
     * @return void
     */
    public function deleteFields($table, $fields)
    {
        if ($this->exist($table) == false) {
            return;
        }

        $tbl_fields   = $this->getFields($table);
        $table_fields = QUI\Utils\ArrayHelper::toAssoc($tbl_fields);

        // prüfen ob die Tabelle leer wäre wenn alle Felder gelöscht werden
        // wenn ja, Tabelle löschen
        foreach ($fields as $field => $type) {
            if (isset($table_fields[$field])) {
                unset($table_fields[$field]);
            }
        }

        if (empty($table_fields)) {
            $this->delete($table);

            return;
        }

        // Einzeln die Felder löschen
        foreach ($fields as $field) {
            if (in_array($field, $tbl_fields)) {
                $this->deleteColumn($table, $field);
            }
        }
    }

    /**
     * Column Methods
     */

    /**
     * Prüft ob eine Spalte in der Tabelle existiert
     *
     * @param string $table
     * @param string $row
     *
     * @return boolean
     */
    public function existColumnInTable($table, $row)
    {
        if ($this->_isSQLite() == false) {
            $PDO   = $this->_DB->getPDO();
            $table = $this->_clear($table);

            $Stmnt = $PDO->prepare("SHOW COLUMNS FROM `{$table}` LIKE :row");
            $Stmnt->bindParam(':row', $row, \PDO::PARAM_STR);
            $Stmnt->execute();

            $data = $Stmnt->fetchAll();

            return count($data) > 0 ? true : false;
        }


        // sqlite part
        $columns = $this->getFields($table);

        foreach ($columns as $col) {
            if ($col == $row) {
                return true;
            }
        }

        return false;
    }

    /**
     * Alle Spalten der Tabelle bekommen
     *
     * @param string $table
     *
     * @return array
     */
    public function getColumns($table)
    {
        return $this->getFields($table);
    }

    /**
     * Return the informations of a column
     *
     * @param string $table - Table name
     * @param string $column - Row name
     *
     * @return array
     */
    public function getColumn($table, $column)
    {
        $PDO   = $this->_DB->getPDO();
        $table = $this->_clear($table);

        if ($this->_isSQLite()) {
            $Stmnt = $PDO->prepare("PRAGMA table_info(`{$table}`);");
            $Stmnt->execute();

            return $Stmnt->fetch();
        }

        $Stmnt = $PDO->prepare("SHOW COLUMNS FROM `{$table}` LIKE :column");
        $Stmnt->bindParam(':column', $column, \PDO::PARAM_STR);
        $Stmnt->execute();

        return $Stmnt->fetch();
    }

    /**
     * Löscht eine Spalte aus der Tabelle
     *
     * @param string $table
     * @param string $row
     *
     * @return boolean
     */
    public function deleteColumn($table, $row)
    {
        if (!$this->existColumnInTable($table, $row)) {
            return true;
        }

        $PDO = $this->_DB->getPDO();

        $table = $this->_clear($table);
        $row   = $this->_clear($row);

        $Stmnt = $PDO->prepare("ALTER TABLE `{$table}` DROP `{$row}`");

        return $Stmnt->execute();
    }

    /**
     * Key Methods
     */

    /**
     * Schlüssel der Tabelle bekommen
     *
     * @param string $table
     * @param boolean $keyNamesOnly (optional) - Nur die Namen der Schlüssel (sonst alle Spalten-Informationen) [default: true]
     * @param boolean $primaryKeysOnly (optional) - Nur Primärschlüssel [default: false]
     *
     * @return array
     */
    /**
     * Schlüssel der Tabelle bekommen
     *
     * @param string $table
     *
     * @return array
     */
    public function getKeys($table)
    {
        $PDO   = $this->_DB->getPDO();
        $table = $this->_clear($table);

        if ($this->_isSQLite()) {
            $Stmt = $PDO->prepare("PRAGMA table_info(`{$table}`)");
        } else {
            $Stmt = $PDO->prepare("SHOW KEYS FROM `{$table}`");
        }

        $Stmt->execute();

        $result = $Stmt->fetchAll();

        if ($this->_isSQLite()) {
            foreach ($result as $k => $row) {
                if (isset($row['pk']) && $row['pk'] != 1) {
                    unset($result[$k]);
                }
            }
        }

        return $result;
    }

    /**
     * Prüft ob der PrimaryKey gesetzt ist
     *
     * @param string $table
     * @param string|array $key
     *
     * @return boolean
     */
    public function issetPrimaryKey($table, $key)
    {
        if (is_array($key)) {
            foreach ($key as $entry) {
                if ($this->_issetPrimaryKey($table, $entry) == false) {
                    return false;
                }
            }

            return true;
        }

        return $this->_issetPrimaryKey($table, $key);
    }

    /**
     * Helper for issetPrimaryKey
     *
     * @see issetPrimaryKey
     *
     * @param string $table
     * @param string $key
     *
     * @return boolean
     */
    protected function _issetPrimaryKey($table, $key)
    {
        $keys = $this->getKeys($table);

        if ($this->_isSQLite()) {
            foreach ($keys as $entry) {
                if (isset($entry['name']) && $entry['name'] == $key
                    && isset($entry['pk'])
                    && $entry['pk'] == 1
                ) {
                    return true;
                }
            }

            return false;
        }

        foreach ($keys as $entry) {
            if (isset($entry['Column_name']) && $entry['Column_name'] == $key) {
                return true;
            }
        }

        return false;
    }

    /**
     * Setzt ein PrimaryKey einer Tabelle
     *
     * @param string $table
     * @param string|array $key
     *
     * @return boolean
     */
    public function setPrimaryKey($table, $key)
    {
        // You can't modify SQLite tables in any significant way after they have been created
        if ($this->_isSQLite()) {
            return true;
        }

        if ($this->issetPrimaryKey($table, $key)) {
            return true;
        }

        $queryKeys  = $this->_inList($key);
        $queryTable = $this->_clear($table);

        $PDO = $this->_DB->getPDO();

        $Stmnt
            = $PDO->prepare("ALTER TABLE `{$queryTable}` ADD PRIMARY KEY({$queryKeys})");
        $Stmnt->execute();

        return $this->issetPrimaryKey($table, $key);
    }

    /**
     * Unique-Spalten einer Tabelle bekommen
     *
     * @param string $table
     * @param boolean $keyNamesOnly (optional) - Nur die Namen der Schlüssel (sonst alle Spalten-Informationen) [default: true]
     * @param boolean $primaryKeysOnly (optional) - Nur Primärschlüssel [default: false]
     *
     * @return array
     */
    /**
     * Schlüssel der Tabelle bekommen
     *
     * @param string $table
     *
     * @return array
     */
    public function getUniqueColumns($table)
    {
        $PDO   = $this->_DB->getPDO();
        $table = $this->_clear($table);

        // @todo implement sqlite query
        if ($this->_isSQLite()) {
            return array();
        }

        $query = "SHOW INDEXES FROM `{$table}`";
        $query .= " WHERE non_unique = 0 AND Key_name != 'PRIMARY';";

        $Stmt = $PDO->prepare($query);
        $Stmt->execute();
        $result = $Stmt->fetchAll();

        $columns = array();

        foreach ($result as $k => $row) {
            if (isset($row['Column_name'])) {
                $columns[] = $row['Column_name'];
            }
        }

        return $result;
    }

    /**
     * Setzt ein UNIQUE-Spalten einer Tabelle
     *
     * @param string $table
     * @param string|array $unique
     *
     * @return boolean
     */
    public function setUniqueColumns($table, $unique)
    {
        // You can't modify SQLite tables in any significant way after they have been created
        if ($this->_isSQLite()) {
            return true;
        }

        if ($this->issetUniqueColumn($table, $unique)) {
            return true;
        }

        $queryKeys  = $this->_inList($unique);
        $queryTable = $this->_clear($table);

        $PDO = $this->_DB->getPDO();

        $Stmnt = $PDO->prepare(
            "ALTER TABLE `{$queryTable}` ADD UNIQUE({$queryKeys})"
        );
        $Stmnt->execute();

        return $this->issetUniqueColumn($table, $unique);
    }

    /**
     * Prüft ob UNIQUE-Spalten gesetzt sind
     *
     * @param string $table
     * @param string|array $unique
     *
     * @return boolean
     */
    public function issetUniqueColumn($table, $unique)
    {
        if (is_array($unique)) {
            foreach ($unique as $entry) {
                if ($this->_issetUniqueColumn($table, $entry) == false) {
                    return false;
                }
            }

            return true;
        }

        return $this->_issetUniqueColumn($table, $unique);
    }

    /**
     * Helper for issetPrimaryKey
     *
     * @see issetPrimaryKey
     *
     * @param string $table
     * @param string $unique
     *
     * @return boolean
     */
    protected function _issetUniqueColumn($table, $unique)
    {
        if ($this->_isSQLite()) {
            // @todo implement sqlite query
            return false;
        }

        $uniques = $this->getUniqueColumns($table);

        return in_array($unique, $uniques);
    }

    /**
     * Index Methods
     */

    /**
     * Prüft ob ein Index gesetzt ist
     *
     * @param string $table
     * @param string|integer $key
     *
     * @return boolean
     */
    public function issetIndex($table, $key)
    {
        if (is_array($key)) {
            foreach ($key as $entry) {
                if ($this->_issetIndex($table, $entry) == false) {
                    return false;
                }
            }

            return true;
        }

        return $this->_issetIndex($table, $key);
    }

    /**
     * Prüft ob ein Index gesetzt ist -> subroutine
     *
     * @param string $table
     * @param string $key
     *
     * @return boolean
     */
    protected function _issetIndex($table, $key)
    {
        $i = $this->getIndex($table);

        if (!$i || empty($i)) {
            return false;
        }

        if ($this->_isSQLite()) {
            foreach ($i as $key => $value) {
                if ($value == $key) {
                    return true;
                }
            }

            return false;
        }

        foreach ($i as $entry) {
            if (isset($entry['Column_name']) && $entry['Column_name'] == $key) {
                return true;
            }
        }

        return false;
    }

    /**
     * Liefert die Indexes einer Tabelle
     *
     * @param string $table
     *
     * @return array
     */
    public function getIndex($table)
    {
        $PDO = $this->_DB->getPDO();

        if ($this->_isSQLite()) {
            try {
                $result = $PDO->query(
                    "SELECT * FROM sqlite_master WHERE type = 'index'"
                )->fetch();

            } catch (\PDOException $Exception) {
                return array();
            }

            return $result;
        }

        $table = $this->_clear($table);

        $Stmnt = $PDO->prepare("SHOW INDEX FROM `{$table}`");
        $Stmnt->execute();

        return $Stmnt->fetchAll();
    }

    /**
     * Setzt einen Index
     *
     * @param string $table
     * @param string|array $index - Array not working on SQLite
     *
     * @return boolean
     */
    public function setIndex($table, $index)
    {
        if ($this->issetIndex($table, $index)) {
            return true;
        }

        $PDO = $this->_DB->getPDO();

        $queryTable = $this->_clear($table);
        $inList     = $this->_inList($index);


        if ($this->_isSQLite()) {
            $Stmnt
                = $PDO->prepare("CREATE INDEX {$inList} ON `{$queryTable}` ({$inList})");
            $Stmnt->execute();

            return $this->issetIndex($table, $index);
        }

        $Stmnt
            = $PDO->prepare("ALTER TABLE `{$queryTable}` ADD INDEX({$inList})");
        $Stmnt->execute();

        return $this->issetIndex($table, $index);
    }

    /**
     * Set the Autoincrement to the column
     *
     * @param string $table
     * @param string $index
     *
     * @throws QUI\Exception
     */
    public function setAutoIncrement($table, $index)
    {
        if ($this->_isSQLite()) {
            throw new QUI\Exception(
                'You can\'t modify SQLite tables in any significant way after they have been created in SQLite'
            );
        }

        $column = $this->getColumn($table, $index);

        if (!$this->issetIndex($table, $index)) {
            $this->setIndex($table, $index);
        }

        // prepare
        $table = $this->_clear($table);
        $index = $this->_clear($index);

        $columnType = $column['Type'];

        if ($column['Null'] === 'No') {
            $columnValue = ' NOT NULL';
        } else {
            $columnValue = ' NULL';
        }

        $query
            = "
            ALTER TABLE `{$table}`
            MODIFY COLUMN `{$index}`
            {$columnType} {$columnValue} AUTO_INCREMENT
        ";

        $PDO   = $this->_DB->getPDO();
        $Stmnt = $PDO->prepare($query);

        $Stmnt->execute();
    }

    /**
     * Fulltext Methods
     */

    /**
     * Setzt einen Fulltext
     *
     * @param string $table
     * @param string|array $index
     *
     * @return boolean
     * @throws QUI\Exception
     */
    public function setFulltext($table, $index)
    {
        // no fulltext in sqlite
        if ($this->_isSQLite()) {
            throw new QUI\Exception('Use USING fts4 for SQLite');
        }

        if ($this->issetFulltext($table, $index)) {
            return true;
        }

        $fulltext = $this->_inList($index);

        $PDO   = $this->_DB->getPDO();
        $table = $this->_clear($table);

        $Stmnt
            = $PDO->prepare("ALTER TABLE `{$table}` ADD FULLTEXT({$fulltext})");
        $Stmnt->execute();

        return $this->issetFulltext($table, $index);
    }

    /**
     * Prüft ob ein Fulltext auf das Feld gesetzt ist
     *
     * @param string $table
     * @param string|integer $key
     *
     * @return boolean
     * @throws QUI\Exception
     */
    public function issetFulltext($table, $key)
    {
        if ($this->_isSQLite()) {
            throw new QUI\Exception('Use USING fts4 for SQLite');
        }

        if (is_array($key)) {
            foreach ($key as $entry) {
                if ($this->_issetFulltext($table, $entry) == false) {
                    return false;
                }
            }

            return true;
        }

        return $this->_issetFulltext($table, $key);
    }

    /**
     * Prüft ob ein Fulltext auf das Feld gesetzt ist -> subroutine
     *
     * @param string $table
     * @param string $key
     *
     * @return boolean
     */
    protected function _issetFulltext($table, $key)
    {
        $keys = $this->getKeys($table);

        foreach ($keys as $entry) {
            if (isset($entry['Column_name']) && isset($entry['Index_type'])
                && $entry['Column_name'] == $key
                && $entry['Index_type'] == 'FULLTEXT'
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Clear the table, column or field name
     *
     * @param string $str
     *
     * @return string
     */
    protected function _clear($str)
    {
        return str_replace(array('\\', "\0", '`'), '', $str);
    }

    /**
     * Parse a mysql field type, and return the clean type
     *
     * @param string $fieldType
     *
     * @return string
     */
    protected function _parseFieldType($fieldType)
    {
        $fieldType = preg_replace("/[^a-zA-Z0-9() ]/", "", $fieldType);
        $fieldType = strtoupper($fieldType);

        return $fieldType;
    }

    /**
     * Prepare a array or a string for an IN LIST() argument
     *
     * @param array|string $index
     *
     * @return string
     */
    protected function _inList($index)
    {
        if (is_array($index)) {
            foreach ($index as $k => $v) {
                $index[$k] = $this->_clear($v);
            }
        }

        if (is_array($index)) {
            $fulltext = "`" . implode("`,`", $index) . "`";
        } else {
            $index    = $this->_clear($index);
            $fulltext = "`{$index}`";
        }

        return $fulltext;
    }
}
