<?php

/**
 * This file contains the \QUI\Database\Tables
 */

namespace QUI\Database;

use PDO;
use PDOException;
use QUI;

use QUI\Exception;

use function count;
use function implode;
use function in_array;
use function is_array;
use function preg_replace;
use function str_replace;
use function stripos;
use function strtoupper;
use function substr;
use function trim;

/**
 * QUIQQER DataBase Layer for table operations
 *
 * @uses    PDO
 * @author  www.pcsg.de (Henning Leutz)
 */
class Tables
{
    /**
     * internal db object
     *
     * @var DB|null
     */
    protected ?DB $DB = null;

    /**
     * Konstruktor
     *
     * @param DB $DB
     */
    public function __construct(DB $DB)
    {
        $this->DB = $DB;
    }

    /**
     * Returns all tables in the database
     *
     * @return array
     */
    public function getTables(): array
    {
        $tables = [];
        $PDO = $this->DB->getPDO();
        $result = $PDO->query("SHOW tables")->fetchAll();

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
     * @param array|string $tables
     *
     * @return void
     */
    public function optimize(array | string $tables): void
    {
        $inList = $this->inList($tables);

        $PDO = $this->DB->getPDO();
        $PDO->prepare("OPTIMIZE TABLE $inList")->execute();
    }

    /**
     * Exist the table?
     *
     * @param string $table - Tabellenname welcher gesucht wird
     *
     * @return boolean
     */
    public function exist(string $table): bool
    {
        $PDO = $this->DB->getPDO();
        $table = $this->clear($table);
        $dbname = $this->DB->getAttribute('dbname');

        $Stmnt = $PDO->prepare("SHOW TABLES FROM `$dbname` LIKE :table");
        $Stmnt->bindParam(':table', $table);
        $Stmnt->execute();

        $data = $Stmnt->fetchAll();

        return count($data) > 0;
    }

    /**
     * Delete a table
     *
     * @param string $table
     *
     * @return void
     */
    public function delete(string $table): void
    {
        if (!$this->exist($table)) {
            return;
        }

        $table = $this->clear($table);
        $PDO = $this->DB->getPDO();

        $PDO->prepare("DROP TABLE `$table`")->execute();
    }

    /**
     * Execute a TRUNCATE on a table
     * empties a table completely
     *
     * @param string $table
     */
    public function truncate(string $table): void
    {
        if (!$this->exist($table)) {
            return;
        }

        $PDO = $this->DB->getPDO();
        $queryTable = $this->clear($table);

        $PDO->prepare("TRUNCATE TABLE `$queryTable`")->execute();
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
     * @throws Exception
     * @todo check mysql injection
     */
    public function create(string $table, array $fields, string $engine = 'InnoDB'): bool
    {
        $_table = $this->clear($table);

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
                $engine = 'InnoDB';
                break;
        }

        $sql = 'CREATE TABLE `' . $this->DB->getAttribute('dbname') . '`.`' . $_table . '` (';

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

        $sql .= ') ENGINE = ' . $engine . ' DEFAULT CHARSET = utf8;';

        try {
            $this->DB->getPDO()->exec($sql);
        } catch (PDOException $Exception) {
            $message = $Exception->getMessage();
            $message .= PHP_EOL;
            $message .= PHP_EOL;
            $message .= $sql;

            throw new Exception($message, $Exception->getCode());
        }

        return $this->exist($table);
    }

    /**
     * Set a comment to a table
     *
     * @param string $table
     * @param string $comment
     */
    public function setComment(string $table, string $comment): void
    {
        $PDO = $this->DB->getPDO();
        $comment = trim($comment);
        $comment = $PDO->quote($comment);

        $query = "ALTER TABLE $table COMMENT = $comment;";
        $Stmnt = $PDO->prepare($query);

        try {
            $Stmnt->execute();
        } catch (\Exception $Exception) {
            if (class_exists('QUI\System\Log')) {
                QUI\System\Log::addInfo($query . ' :: ' . $Exception->getMessage());
            }
        }
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
     * @deprecated use ->getColumns
     */
    public function getFields(string $table): array
    {
        return $this->getColumns($table);
    }

    /**
     * Tabellen-Spalten mit detailliertern Spalten-Informationen
     *
     * @param string $table - Tabelle
     *
     * @return array
     */
    public function getFieldsInfos(string $table): array
    {
        $PDO = $this->DB->getPDO();
        $table = $this->clear($table);
        $Stmnt = $PDO->prepare("SHOW COLUMNS FROM `$table`");
        $Stmnt->execute();

        return $Stmnt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Erweitert Tabellen mit den Feldern
     * Wenn die Tabelle nicht existiert wird diese erstellt
     *
     * @param string $table
     * @param array $fields
     * @param string $engine - optional, is only used when the table is created
     *
     * @throws Exception
     * @deprecated ->addColumn
     *
     */
    public function appendFields(string $table, array $fields, string $engine = 'InnoDB'): void
    {
        $this->addColumn($table, $fields, $engine);
    }

    /**
     * Löscht ein Feld / Spalte aus der Tabelle
     *
     * @param string $table
     * @param array $fields
     *
     * @return void
     */
    public function deleteFields(string $table, array $fields): void
    {
        if (!$this->exist($table)) {
            return;
        }

        $tbl_fields = $this->getColumns($table);
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
     * Extend the table
     *
     * @param string $table
     * @param array $fields
     * @param string $engine
     * @throws Exception
     * @throws \Exception
     */
    public function addColumn(string $table, array $fields, string $engine = 'InnoDB'): void
    {
        if (!$this->exist($table)) {
            $this->create($table, $fields, $engine);

            return;
        }

        $tblFields = $this->getColumns($table);

        $PDO = $this->DB->getPDO();
        $table = $this->clear($table);
        $change = [];

        foreach ($fields as $field => $type) {
            $field = $this->clear($field);
            $type = $this->parseFieldType($type);

            if (!in_array($field, $tblFields)) {
                $query = "ALTER TABLE `$table` ADD `$field` $type";
                $Stmnt = $PDO->prepare($query);

                try {
                    $Stmnt->execute();
                } catch (\Exception $Exception) {
                    if (class_exists('QUI\System\Log')) {
                        QUI\System\Log::addError($query);
                    }

                    throw $Exception;
                }

                continue;
            }

            // change column
            if (
                stripos($type, 'PRIMARY KEY') === false &&
                !$this->issetPrimaryKey($table, $field) &&
                !$this->issetIndex($table, $field)
            ) {
                $change[] = "CHANGE `$field` `$field` $type";
            }
        }

        if (!count($change)) {
            return;
        }

        $query = "ALTER TABLE `$table` ";
        $query .= implode(",\n", $change);

        $query = str_replace('CURRENTTIMESTAMP', 'NOW()', $query);
        $query = str_replace('DEFAULT  NOT NULL', "DEFAULT '' NOT NULL", $query);

        $Stmnt = $PDO->prepare($query);

        try {
            $Stmnt->execute();
        } catch (\Exception $Exception) {
            if (class_exists('QUI\System\Log')) {
                QUI\System\Log::addError($query);
            }

            throw $Exception;
        }
    }

    /**
     * Prüft ob eine Spalte in der Tabelle existiert
     *
     * @param string $table
     * @param string $row
     *
     * @return boolean
     */
    public function existColumnInTable(string $table, string $row): bool
    {
        $PDO = $this->DB->getPDO();
        $table = $this->clear($table);

        $Stmnt = $PDO->prepare("SHOW COLUMNS FROM `$table` WHERE `Field` = :row");
        $Stmnt->bindParam(':row', $row);
        $Stmnt->execute();

        $data = $Stmnt->fetchAll();

        return count($data) > 0;
    }

    /**
     * Alle Spalten der Tabelle bekommen
     *
     * @param string $table
     *
     * @return array
     */
    public function getColumns(string $table): array
    {
        $PDO = $this->DB->getPDO();
        $table = $this->clear($table);
        $Stmnt = $PDO->prepare("SHOW COLUMNS FROM `$table`");
        $Stmnt->execute();

        $result = $Stmnt->fetchAll(PDO::FETCH_ASSOC);
        $fields = [];

        foreach ($result as $entry) {
            $fields[] = $entry['Field'];
        }

        return $fields;
    }

    /**
     * Return the informations of a column
     *
     * @param string $table - Table name
     * @param string $column - Row name
     *
     * @return array
     */
    public function getColumn(string $table, string $column): array
    {
        $PDO = $this->DB->getPDO();
        $table = $this->clear($table);

        $Stmnt = $PDO->prepare("SHOW COLUMNS FROM `$table` LIKE :column");
        $Stmnt->bindParam(':column', $column);
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
    public function deleteColumn(string $table, string $row): bool
    {
        if (!$this->existColumnInTable($table, $row)) {
            return true;
        }

        $PDO = $this->DB->getPDO();

        $table = $this->clear($table);
        $row = $this->clear($row);
        $Stmnt = $PDO->prepare("ALTER TABLE `$table` DROP `$row`");

        return $Stmnt->execute();
    }

    /**
     * Key Methods
     */

    /**
     * Schlüssel der Tabelle bekommen
     *
     * @param string $table
     * @param boolean $keyNamesOnly (optional) - Nur die Namen der Schlüssel
     *                                           (sonst alle Spalten-Informationen) [default: true]
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
    public function getKeys(string $table): array
    {
        $PDO = $this->DB->getPDO();
        $table = $this->clear($table);
        $Stmt = $PDO->prepare("SHOW KEYS FROM `$table`");
        $Stmt->execute();

        return $Stmt->fetchAll();
    }

    /**
     * Prüft ob der PrimaryKey gesetzt ist
     *
     * @param string $table
     * @param array|string $key
     *
     * @return boolean
     */
    public function issetPrimaryKey(string $table, array | string $key): bool
    {
        if (is_array($key)) {
            foreach ($key as $entry) {
                if (!$this->issetPrimaryKeyHelper($table, $entry)) {
                    return false;
                }
            }

            return true;
        }

        return $this->issetPrimaryKeyHelper($table, $key);
    }

    /**
     * Helper for issetPrimaryKey
     *
     * @param string $table
     * @param string $key
     *
     * @return boolean
     * @see issetPrimaryKey
     *
     */
    protected function issetPrimaryKeyHelper(string $table, string $key): bool
    {
        $keys = $this->getKeys($table);

        foreach ($keys as $entry) {
            if (
                isset($entry['Column_name'])
                && $entry['Column_name'] == $key
                && $entry['Key_name'] === 'PRIMARY'
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Setzt ein PrimaryKey einer Tabelle
     *
     * @param string $table
     * @param array|string $key
     *
     * @return boolean
     */
    public function setPrimaryKey(string $table, array | string $key): bool
    {
        if ($this->issetPrimaryKey($table, $key)) {
            return true;
        }

        $queryKeys = $this->inList($key);
        $queryTable = $this->clear($table);

        $PDO = $this->DB->getPDO();
        $query = "ALTER TABLE `$queryTable` ADD PRIMARY KEY($queryKeys)";

        // if key exists, drop it
        if (is_array($key)) {
            foreach ($key as $k) {
                if ($this->issetPrimaryKey($table, $k)) {
                    $query = "ALTER TABLE  `$queryTable` DROP PRIMARY KEY , ADD PRIMARY KEY($queryKeys);";
                    break;
                }
            }
        }

        $Stmnt = $PDO->prepare($query);
        $Stmnt->execute();

        return $this->issetPrimaryKey($table, $key);
    }

    /**
     * Unique-Spalten einer Tabelle bekommen
     *
     * @param string $table
     * @param boolean $keyNamesOnly (optional) - Nur die Namen der Schlüssel
     *                                           (sonst alle Spalten-Informationen) [default: true]
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
    public function getUniqueColumns(string $table): array
    {
        $PDO = $this->DB->getPDO();
        $table = $this->clear($table);

        $query = "SHOW INDEXES FROM `$table`";
        $query .= " WHERE non_unique = 0 AND Key_name != 'PRIMARY';";

        $Stmt = $PDO->prepare($query);
        $Stmt->execute();
        $result = $Stmt->fetchAll();

        $columns = [];

        foreach ($result as $row) {
            if (isset($row['Column_name'])) {
                $columns[] = $row['Column_name'];
            }
        }

        return $columns;
    }

    /**
     * Setzt ein UNIQUE-Spalten einer Tabelle
     *
     * @param string $table
     * @param array|string $unique
     *
     * @return boolean
     */
    public function setUniqueColumns(string $table, array | string $unique): bool
    {
        if ($this->issetUniqueColumn($table, $unique)) {
            return true;
        }

        $queryKeys = $this->inList($unique);
        $queryTable = $this->clear($table);

        $PDO = $this->DB->getPDO();

        $Stmnt = $PDO->prepare(
            "ALTER TABLE `$queryTable` ADD UNIQUE($queryKeys)"
        );
        $Stmnt->execute();

        return $this->issetUniqueColumn($table, $unique);
    }

    /**
     * Prüft ob UNIQUE-Spalten gesetzt sind
     *
     * @param string $table
     * @param array|string $unique
     *
     * @return boolean
     */
    public function issetUniqueColumn(string $table, array | string $unique): bool
    {
        if (is_array($unique)) {
            foreach ($unique as $entry) {
                if (!$this->issetUniqueColumnHelper($table, $entry)) {
                    return false;
                }
            }

            return true;
        }

        return $this->issetUniqueColumnHelper($table, $unique);
    }

    /**
     * Helper for issetPrimaryKey
     *
     * @param string $table
     * @param string $unique
     *
     * @return boolean
     * @see issetPrimaryKey
     *
     */
    protected function issetUniqueColumnHelper(string $table, string $unique): bool
    {
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
     * @param int|string|array $key
     *
     * @return boolean
     */
    public function issetIndex(string $table, int | string | array $key): bool
    {
        if (is_array($key)) {
            foreach ($key as $entry) {
                if (!$this->issetIndexHelper($table, $entry)) {
                    return false;
                }
            }

            return true;
        }

        return $this->issetIndexHelper($table, $key);
    }

    /**
     * Prüft ob ein Index gesetzt ist -> subroutine
     *
     * @param string $table
     * @param string $key
     *
     * @return boolean
     */
    protected function issetIndexHelper(string $table, string $key): bool
    {
        $i = $this->getIndex($table);

        if (empty($i)) {
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
     * Return the current AUTO_INCREMENT of a table
     *
     * @param string $table
     * @return int
     * @throws Exception|\Doctrine\DBAL\Exception
     */
    public function getAutoIncrementIndex(string $table): int
    {
        /**
         * MySQL 8 introduced the variable 'information_schema_stats_expiry'
         * which sets the caching time of table metadata (SHOW TABLE STATUS...).
         *
         * We have to disable this cache to get the true current AUTO_INCREMENT value.
         *
         * Thus, we have to check first if this variable exists.
         */
        $mysql8CheckQuery = "SHOW VARIABLES LIKE '%information_schema_stats_expiry%'";
        $result = $this->DB->fetchSQL($mysql8CheckQuery);
        $isMySql8 = !empty($result);

        if ($isMySql8) {
            $this->DB->execSQL("SET SESSION information_schema_stats_expiry=0");
        }

        $table = $this->clear($table);
        $statusQuery = "SHOW TABLE STATUS WHERE name = '$table';";

        $PDO = $this->DB->getPDO();
        $Statement = $PDO->prepare($statusQuery);
        $Statement->execute();

        $result = $Statement->fetchAll();

        if (!isset($result[0])) {
            return 0;
        }

        if (isset($result[0]['AUTO_INCREMENT'])) {
            return (int)$result[0]['AUTO_INCREMENT'];
        }

        if (isset($result[0]['Auto_increment'])) {
            return (int)$result[0]['Auto_increment'];
        }

        return 0;
    }

    /**
     * Liefert die Indexes einer Tabelle
     *
     * @param string $table
     *
     * @return array
     */
    public function getIndex(string $table): array
    {
        $PDO = $this->DB->getPDO();
        $table = $this->clear($table);
        $Stmnt = $PDO->prepare("SHOW INDEX FROM `$table`");
        $Stmnt->execute();

        return $Stmnt->fetchAll();
    }

    /**
     * Setzt einen Index
     *
     * @param string $table
     * @param array|string $index - Array not working on SQLite
     *
     * @return boolean
     */
    public function setIndex(string $table, array | string $index): bool
    {
        if ($this->issetIndex($table, $index)) {
            return true;
        }

        $PDO = $this->DB->getPDO();
        $queryTable = $this->clear($table);
        $inList = $this->inList($index);

        $Stmnt = $PDO->prepare("ALTER TABLE `$queryTable` ADD INDEX($inList)");
        $Stmnt->execute();

        return $this->issetIndex($table, $index);
    }

    /**
     * Set the Autoincrement to the column
     *
     * @param string $table
     * @param string $index
     */
    public function setAutoIncrement(string $table, string $index): void
    {
        $column = $this->getColumn($table, $index);

        if (!$this->issetIndex($table, $index)) {
            $this->setIndex($table, $index);
        }

        // prepare
        $table = $this->clear($table);
        $index = $this->clear($index);

        $columnType = $column['Type'];

        if ($column['Null'] === 'No') {
            $columnValue = ' NOT NULL';
        } else {
            $columnValue = '';
        }

        $query = "
            ALTER TABLE `$table`
            MODIFY COLUMN `$index`
            $columnType $columnValue AUTO_INCREMENT
        ";

        $PDO = $this->DB->getPDO();
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
     * @param array|string $index
     *
     * @return boolean
     */
    public function setFulltext(string $table, array | string $index): bool
    {
        if ($this->issetFulltext($table, $index)) {
            return true;
        }

        $fulltext = $this->inList($index);

        $PDO = $this->DB->getPDO();
        $table = $this->clear($table);

        $Stmnt = $PDO->prepare(
            "ALTER TABLE `$table` ADD FULLTEXT($fulltext)"
        );

        $Stmnt->execute();

        return $this->issetFulltext($table, $index);
    }

    /**
     * Prüft ob ein Fulltext auf das Feld gesetzt ist
     *
     * @param string $table
     * @param int|string|array $key
     *
     * @return boolean
     */
    public function issetFulltext(string $table, int | string | array $key): bool
    {
        if (is_array($key)) {
            foreach ($key as $entry) {
                if (!$this->issetFulltextHelper($table, $entry)) {
                    return false;
                }
            }

            return true;
        }

        return $this->issetFulltextHelper($table, $key);
    }

    /**
     * Prüft ob ein Fulltext auf das Feld gesetzt ist -> subroutine
     *
     * @param string $table
     * @param string $key
     *
     * @return boolean
     */
    protected function issetFulltextHelper(string $table, string $key): bool
    {
        $keys = $this->getKeys($table);

        foreach ($keys as $entry) {
            if (
                isset($entry['Column_name']) && isset($entry['Index_type'])
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
    protected function clear(string $str): string
    {
        return str_replace(['\\', "\0", '`'], '', $str);
    }

    /**
     * Parse a mysql field type, and return the clean type
     *
     * @param string $fieldType
     *
     * @return string
     */
    protected function parseFieldType(string $fieldType): string
    {
        $fieldType = preg_replace("/[^a-zA-Z0-9() '_,]/", "", $fieldType);

        return strtoupper($fieldType);
    }

    /**
     * Prepare a array or a string for an IN LIST() argument
     *
     * @param array|string $index
     *
     * @return string
     */
    protected function inList(array | string $index): string
    {
        if (is_array($index)) {
            foreach ($index as $k => $v) {
                $index[$k] = $this->clear($v);
            }
        }

        if (is_array($index)) {
            $fulltext = "`" . implode("`,`", $index) . "`";
        } else {
            $index = $this->clear($index);
            $fulltext = "`$index`";
        }

        return $fulltext;
    }
}
