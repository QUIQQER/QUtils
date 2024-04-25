<?php

/**
 * This file contains the \QUI\Database\DB
 */

namespace QUI\Database;

use DateTime;
use Doctrine\DBAL\Connection;
use PDO;
use PDOException;
use PDOStatement;
use QUI;
use QUI\Utils\Security\Orthos;

use function abs;
use function array_flip;
use function array_merge;
use function array_unique;
use function class_exists;
use function count;
use function explode;
use function implode;
use function is_array;
use function is_null;
use function is_string;
use function mb_substr;
use function microtime;
use function preg_match;
use function preg_replace;
use function print_r;
use function round;
use function str_replace;
use function strlen;
use function strtolower;
use function strtoupper;
use function trim;

/**
 * QUIQQER DataBase Layer
 *
 * @uses    PDO
 * @author  www.pcsg.de (Henning Leutz)
 */
class DB extends QUI\QDOM
{
    /**
     * PDO Object
     *
     * @var PDO|null
     */
    protected ?PDO $PDO = null;

    protected ?\Doctrine\DBAL\Connection $Doctrine = null;

    /**
     * DBTable Object
     *
     * @var Tables|null
     */
    protected ?Tables $Tables = null;

    /**
     * SQLite Flag
     *
     * @var boolean
     */
    protected bool $sqlite = false;

    /**
     * @var bool|string
     */
    protected string|bool $version = false;

    /**
     * indicates when the connection should be re-established
     *
     * @var int
     */
    protected int $reconnectTimeout = 25000;

    /**
     * Time of the last connection
     *
     * @var int
     */
    protected int $lastConnectTime = 0;

    /**
     * Constructor
     *
     * @param array $attributes
     * - host
     * - user
     * - password
     * - dbname
     * - options (optional)
     * - driver (optional)
     *
     * @throws
     */
    public function __construct(array $attributes = [])
    {
        if (class_exists('\Doctrine\DBAL\Connection') && isset($attributes['doctrine'])) {
            $Doctrine = $attributes['doctrine'];

            if ($Doctrine instanceof \Doctrine\DBAL\Connection) {
                $this->Doctrine = $Doctrine;
                $this->setAttribute('dbname', $this->Doctrine->getDatabase());
                $Native = $this->Doctrine->getNativeConnection();

                if ($Native instanceof PDO) {
                    $this->PDO = $Native;
                }
            }
        }

        if ($this->PDO === null) {
            $this->setAttribute('host', 'localhost');
            $this->setAttribute('driver', 'mysql');
            $this->setAttribute('options', [
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ]);

            if (isset($attributes['driver']) && empty($attributes['driver'])) {
                unset($attributes['driver']);
            }

            $this->setAttributes($attributes);
            $this->PDO = $this->getNewPDO();
        }

        $this->Tables = new Tables($this);
    }

    /**
     * Return a new PDO Object
     * This Object generate a new database connection
     *
     * attention: please use getPDO() if you want not a new database connection
     *
     * @return PDO
     * @throws Exception
     */
    public function getNewPDO(): PDO
    {
        if ($this->Doctrine) {
            return $this->PDO;
        }

        $this->lastConnectTime = time();

        if ($this->getAttribute('dsn') === false) {
            $dsn = $this->getAttribute('driver') .
                ':dbname=' . $this->getAttribute('dbname') .
                ';host=' . $this->getAttribute('host');

            if ($this->getAttribute('port')) {
                $dsn .= ';port=' . $this->getAttribute('port');
            }

            $this->setAttribute('dsn', $dsn);
        }

        // sqlite PDO
        try {
            if ($this->getAttribute('driver') == 'sqlite') {
                $this->sqlite = true;

                $Pdo = new PDO(
                    'sqlite:' . $this->getAttribute('dbname')
                );
            } else {
                $Pdo = new PDO(
                    $this->getAttribute('dsn'),
                    $this->getAttribute('user'),
                    $this->getAttribute('password'),
                    $this->getAttribute('options')
                );
            }

            $Pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            try {
                $Date = new DateTime();
                $offset = $Date->getOffset();

                $offsetHours = round(abs($offset) / 3600);
                $offsetMinutes = round((abs($offset) - $offsetHours * 3600) / 60);

                $offsetString = ($offset < 0 ? '-' : '+');
                $offsetString .= (strlen($offsetHours) < 2 ? '0' : '') . $offsetHours;
                $offsetString .= ':';
                $offsetString .= (strlen($offsetMinutes) < 2 ? '0' : '') . $offsetMinutes;

                $Pdo->exec("SET time_zone = '$offsetString'");
            } catch (PDOException $Exception) {
                QUI\System\Log::addError($Exception->getMessage());
            }

            return $Pdo;
        } catch (PDOException $Exception) {
            throw new QUI\Database\Exception(
                $Exception->getMessage(),
                $Exception->getCode()
            );
        }
    }

    /**
     * Return the internal PDO Object
     *
     * @return PDO|null
     */
    public function getPDO(): ?PDO
    {
        return $this->PDO;
    }

    // region reconnection

    /**
     * Reconnect the pdo connection to the sql server
     *
     * @throws Exception
     */
    public function reconnect(): void
    {
        if ($this->Doctrine) {
            return;
        }

        if ($this->PDO) {
            $this->PDO = null;
        }

        $this->PDO = $this->getNewPDO();
    }

    /**
     * Set the reconnect timeout
     *
     * @param int $time - seconds
     */
    public function setReconnectTimeout(int $time): void
    {
        $this->reconnectTimeout = $time;
    }

    /**
     * Reconnect the connection if needed
     *
     * @throws Exception
     */
    protected function reconnectCheck(): void
    {
        $diff = time() - $this->lastConnectTime;

        if ($diff > $this->reconnectTimeout) {
            $this->reconnect();
        }
    }

    //endregion

    /**
     * Return the server version of the database
     *
     * @return string|bool
     */
    public function getVersion(): string|bool
    {
        if (!$this->version) {
            $this->version = $this->PDO->query('select version()')->fetchColumn();

            preg_match("/^[0-9\.]+/", $this->version, $match);

            $this->version = $match[0];
        }

        return $this->version;
    }

    /**
     * Database object for tables
     *
     * @return Tables|null
     */
    public function table(): ?Tables
    {
        if ($this->Tables === null) {
            $this->Tables = new Tables($this);
        }

        return $this->Tables;
    }

    /**
     * Is the DB a sqlite db?
     *
     * @return boolean
     */
    public function isSQLite(): bool
    {
        return $this->sqlite;
    }

    /**
     * Creates a query
     *
     * @param array $params
     *        array(
     *        'insert'  => 'table'
     *        'replace' => 'table'
     *        'update'  => 'table'
     *        'delete'  => 'table'
     *        'count'   => 'field'
     *
     *        'from' => array('table1', 'table2'),
     *        'from' => 'table1',
     *
     *        'set' => array(
     *            'field1' => 'value1',
     *            'field2' => 'value2',
     *            'field3' => 'value3',
     *        ),
     *        'where' => array(
     *            'field1' => 'value1',
     *            'field2' => 'value2',
     *        ),
     *        'where_or' => array(
     *            'field1' => 'value1',
     *            'field2' => 'value2',
     *        ),
     *
     *        'order' => 'string'
     *        'group' => 'string'
     *        'limit' => 'string',
     *
     *        'debug' => true // write the query into the log
     * )
     *
     * @return array
     *    array(
     *        'query'   => string  - SQL String
     *        'prepare' => array() - Prepared Statement Vars
     *    )
     */
    public function createQuery(array $params = []): array
    {
        $query = $this->createQuerySelect($params);
        $prepare = [];

        /**
         * Start Block
         */
        if (!empty($params['insert'])) {
            if ($this->isSQLite() && isset($params['set'])) {
                $insert = $this->createQuerySQLiteInsert($params);

                $query = $insert['insert'];
                $prepare = array_merge($prepare, $insert['prepare']);

                unset($params['set']);
            } else {
                $query = $this->createQueryInsert($params['insert']);
            }
        }

        if (!empty($params['replace'])) {
            $query = $this->createQueryReplace($params['replace']);
        }

        if (!empty($params['update'])) {
            $query = $this->createQueryUpdate($params['update']);
        }

        if (!empty($params['count'])) {
            $query = $this->createQueryCount($params['count']);
        }

        if (isset($params['delete']) && $params['delete'] === true) {
            $query = $this->createQueryDelete();
        }

        /**
         * From Block
         */
        if (!empty($params['from'])) {
            $query .= $this->createQueryFrom($params['from']);
        }

        /**
         * set & where Block
         */
        if (!empty($params['set'])) {
            $set = $this->createQuerySet(
                $params['set'],
                $this->getAttribute('driver')
            );

            $query .= $set['set'];
            $prepare = array_merge($prepare, $set['prepare']);
        }

        if (!empty($params['where'])) {
            $where = $this->createQueryWhere($params['where']);

            $query .= $where['where'];
            $prepare = array_merge($prepare, $where['prepare']);
        }

        if (!empty($params['where_or'])) {
            $where = $this->createQueryWhereOr($params['where_or']);

            if (!str_contains($query, 'WHERE')) {
                $query .= $where['where'];
            } else {
                $query .= ' AND (' . str_replace('WHERE', '', $where['where']) . ')';
            }

            $prepare = array_merge($prepare, $where['prepare']);
        }

        /**
         * Order Block
         */
        if (!empty($params['order'])) {
            $query .= $this->createQueryOrder($params['order']);
        }

        if (!empty($params['group'])) {
            $query .= $this->createQueryGroupBy($params['group']);
        }

        if (!empty($params['limit'])) {
            $limit = $this->createQueryLimit($params['limit']);

            $query .= $limit['limit'];
            $prepare = array_merge($prepare, $limit['prepare']);
        }

        // debugging
        if (isset($params['debug'])) {
            QUI\System\Log::writeRecursive([
                'query' => $query,
                'prepare' => $prepare
            ]);
        }

        return [
            'query' => $query,
            'prepare' => $prepare
        ];
    }

    /**
     * Execute query and returned a PDOStatement
     * (Prepare Statement)
     *
     * @param array $params (see at createQuery())
     *
     * @return PDOStatement
     *
     * @throws QUI\Database\Exception
     */
    public function exec(array $params = []): PDOStatement
    {
        if (class_exists('QUI') && QUI::$Events !== null) {
            try {
                QUI::getEvents()->fireEvent('dataBaseQueryCreate', [$this]);
            } catch (QUI\Exception $Exception) {
                throw new QUI\Database\Exception(
                    $Exception->getMessage(),
                    $Exception->getCode()
                );
            }
        }

        $start = microtime();
        $query = $this->createQuery($params);


        if (class_exists('QUI') && QUI::$Events !== null) {
            try {
                QUI::getEvents()->fireEvent('dataBaseQuery', [$this, $query]);
            } catch (QUI\Exception $Exception) {
                throw new QUI\Database\Exception(
                    $Exception->getMessage(),
                    $Exception->getCode()
                );
            }
        }

        if (isset($params['debug'])) {
            QUI\System\Log::writeRecursive($query);
        }

        $Statement = $this->getPDO()->prepare($query['query'] . ';');

        foreach ($query['prepare'] as $key => $val) {
            if (is_array($val) && isset($val[0])) {
                if (isset($val[1])) {
                    $Statement->bindValue($key, $val[0], $val[1]);
                } else {
                    $Statement->bindValue($key, $val[0]);
                }

                continue;
            }

            $Statement->bindValue($key, $val);
        }

        try {
            $Statement->execute();
        } catch (PDOException $Exception) {
            $message = $Exception->getMessage();
            $message .= print_r($query, true);

            if (class_exists('QUI') && QUI::$Events !== null) {
                try {
                    QUI::getEvents()->fireEvent(
                        'dataBaseQueryEnd',
                        [$this, $query, $start, microtime()]
                    );

                    QUI::getEvents()->fireEvent(
                        'dataBaseQueryError',
                        [$this, $Exception, $query, $start, microtime()]
                    );
                } catch (QUI\Exception $Exception) {
                    throw new QUI\Database\Exception(
                        $Exception->getMessage(),
                        $Exception->getCode()
                    );
                }
            }


            $Exception = new QUI\Database\Exception($message, $Exception->getCode());

            if (class_exists('QUI\System\Log')) {
                QUI\System\Log::addError($Exception->getMessage());
                QUI\System\Log::writeDebugException($Exception);
            }

            throw $Exception;
        }

        if (class_exists('QUI') && QUI::$Events !== null) {
            try {
                QUI::getEvents()->fireEvent('dataBaseQueryEnd', [$this, $query, $start, microtime()]);
            } catch (QUI\Exception $Exception) {
                throw new QUI\Database\Exception(
                    $Exception->getMessage(),
                    $Exception->getCode()
                );
            }
        }

        return $Statement;
    }

    /**
     * Execute query and get as the results
     *
     * @param array $params (see at createQuery())
     * @param integer $FETCH_STYLE - \PDO::FETCH*
     *
     * @return array
     * @throws QUI\Database\Exception
     *
     */
    public function fetch(
        array $params = [],
        int $FETCH_STYLE = PDO::FETCH_ASSOC
    ): array {
        $this->reconnectCheck();

        $Statement = $this->exec($params);

        switch ($FETCH_STYLE) {
            case PDO::FETCH_ASSOC:
            case PDO::FETCH_BOTH:
            case PDO::FETCH_BOUND:
            case PDO::FETCH_CLASS:
            case PDO::FETCH_OBJ:
                break;

            default:
                $FETCH_STYLE = PDO::FETCH_ASSOC;
                break;
        }

        $result = $Statement->fetchAll($FETCH_STYLE);
        $Statement->closeCursor();
        $Statement = null;

        return $result;
    }

    /**
     * Execute the query and dont execute a fetch
     *
     * @param $query
     * @return PDOStatement|false
     * @throws Exception
     *
     */
    public function execSQL($query): bool|PDOStatement
    {
        $Statement = $this->getPDO()->prepare($query);

        try {
            $Statement->execute();
        } catch (PDOException $Exception) {
            $message = $Exception->getMessage();
            $message .= print_r($query, true);

            throw new QUI\Database\Exception($message, $Exception->getCode());
        }

        return $Statement;
    }

    /**
     * Execute query and get results
     * The query is passed directly!
     * Better use ->fetch() and pass the parameters as array
     *
     * @param string $query
     * @param integer $FETCH_STYLE - \PDO::FETCH*
     *
     * @return array
     * @throws QUI\Database\Exception
     *
     */
    public function fetchSQL(string $query, int $FETCH_STYLE = PDO::FETCH_ASSOC): array
    {
        $this->reconnectCheck();

        $Statement = $this->execSQL($query);

        switch ($FETCH_STYLE) {
            case PDO::FETCH_ASSOC:
            case PDO::FETCH_BOTH:
            case PDO::FETCH_BOUND:
            case PDO::FETCH_CLASS:
            case PDO::FETCH_OBJ:
                break;

            default:
                $FETCH_STYLE = PDO::FETCH_ASSOC;
                break;
        }

        return $Statement->fetchAll($FETCH_STYLE);
    }

    /**
     * Updates a record
     *
     * @param string $table
     * @param array $data
     * @param array|string $where
     *
     * @return PDOStatement
     * @throws QUI\Database\Exception
     *
     */
    public function update(string $table, array $data, array|string $where): PDOStatement
    {
        return $this->exec([
            'update' => $table,
            'set' => $data,
            'where' => $where
        ]);
    }

    /**
     * Insert a record
     *
     * @param string $table
     * @param array $data
     *
     * @return PDOStatement
     * @throws QUI\Database\Exception
     *
     */
    public function insert(string $table, array $data): PDOStatement
    {
        return $this->exec([
            'insert' => $table,
            'set' => $data
        ]);
    }

    /**
     * If a dataset's primary/unique already exists, the old dataset is replaced.
     * If the dataset does not exist yet, the dataset is inserted.
     *
     * @param string $table
     * @param array $data
     *
     * @return PDOStatement
     * @throws QUI\Database\Exception
     *
     */
    public function replace(string $table, array $data): PDOStatement
    {
        return $this->exec([
            'replace' => $table,
            'set' => $data
        ]);
    }

    /**
     * Deletes a record
     *
     * @param string $table - Name of the Database Table
     * @param array $where - data field, where statement
     *
     * @return PDOStatement
     * @throws QUI\Database\Exception
     *
     */
    public function delete(string $table, array $where): PDOStatement
    {
        return $this->exec([
            'delete' => true,
            'from' => $table,
            'where' => $where
        ]);
    }

    /**
     * SELECT Query
     *
     * @param array $params
     *
     * 'select' => 'field'
     *
     * 'select' => ['field', 'field']
     *
     * 'select' => [
     *      'field'    => 'fieldName'
     *      'function' => 'COUNT'
     * ]
     *
     * 'select' => [
     *      [
     *          'field'    => 'fieldName'
     *          'function' => 'COUNT'
     *      ],
     *      [
     *          'field'    => 'fieldName'
     *          'function' => 'COUNT'
     *      ]
     * ]
     *
     * @return string
     */
    public static function createQuerySelect(array $params): string
    {
        if (empty($params['select'])) {
            return 'SELECT * ';
        }

        if (is_string($params['select'])) {
            $params['select'] = explode(',', $params['select']);
        }

        // encapsulation
        if (isset($params['select']['field']) || isset($params['select']['function'])) {
            $params['select'] = [$params['select']];
        }

        foreach ($params['select'] as $key => $select) {
            if ($select === '*') {
                $params['select'][$key] = $select;
                continue;
            }

            if (!is_array($select)) {
                $params['select'][$key] = Orthos::cleanupDatabaseFieldName($select);
                continue;
            }

            if (!isset($select['field']) || !isset($select['function'])) {
                continue;
            }

            $fields = $select['field'];

            if (!is_array($fields)) {
                $fields = [$fields];
            }

            foreach ($fields as $k => $f) {
                if ($f === '*') {
                    $fields[$k] = '*';
                    continue;
                }

                $fields[$k] = Orthos::cleanupDatabaseFieldName($f);
            }

            $function = $select['function'];
            $function = preg_replace('/[^0-9,a-zA-Z_]/i', '', $function);
            $function = trim($function);

            $functionParams = implode(',', $fields);

            $params['select'][$key] = $function . '(' . $functionParams . ')';
        }

        return 'SELECT ' . implode(', ', $params['select']) . ' ';
    }

    /**
     * Insert Query
     *
     * @param string $params
     *
     * @return string
     */
    public static function createQueryInsert(string $params): string
    {
        return 'INSERT INTO ' . Orthos::cleanupDatabaseFieldName($params);
    }

    /**
     * Replace Query
     *
     * @param string $params
     *
     * @return string
     */
    public static function createQueryReplace(string $params): string
    {
        return 'REPLACE INTO ' . Orthos::cleanupDatabaseFieldName($params);
    }

    /**
     * Update Query
     *
     * @param string $params
     *
     * @return string
     */
    public static function createQueryUpdate(string $params): string
    {
        return 'UPDATE ' . Orthos::cleanupDatabaseFieldName($params);
    }

    /**
     * Delete Query
     *
     * @return string
     */
    public static function createQueryDelete(): string
    {
        return 'DELETE ';
    }

    /**
     * COUNT() Query
     *
     * @param array|string $params
     *
     * @return string
     */
    public static function createQueryCount(array|string $params): string
    {
        if (is_array($params) && isset($params['select'])) {
            $query = ' SELECT COUNT(';
            $query .= Orthos::cleanupDatabaseFieldName($params['select']);
            $query .= ') ';

            if (isset($params['as'])) {
                $query .= 'AS ' . Orthos::cleanupDatabaseFieldName($params['as']);
            }

            return $query;
        }

        $query = ' SELECT COUNT(*) ';

        if (is_string($params)) {
            $query .= 'AS ' . Orthos::cleanupDatabaseFieldName($params);
        }

        return $query;
    }

    /**
     * FROM Query
     *
     * @param array|string $params
     *
     * @return string
     */
    public static function createQueryFrom(array|string $params): string
    {
        if (is_string($params)) {
            return ' FROM ' . Orthos::cleanupDatabaseFieldName($params);
        }

        if (is_array($params)) {
            $from = array_unique($params);

            foreach ($from as $key => $entry) {
                $from[$key] = Orthos::cleanupDatabaseFieldName($entry);
            }

            return ' FROM ' . implode(',', $from);
        }

        return '';
    }

    /**
     * WHERE Query
     *
     * @param array|string $params
     * @param string $type - if more than one where, you can specify the where typ (OR, AND)
     *
     * @return array
     * array(
     *     'where' => 'WHERE param = :param',
     *     'prepare' => array(
     *         'param' => value
     *     )
     * );
     */
    public static function createQueryWhere(array|string $params, string $type = 'AND'): array
    {
        if (is_string($params)) {
            return [
                'where' => ' WHERE ' . $params,
                'prepare' => []
            ];
        }

        $prepare = [];
        $sql = '';

        if (is_array($params)) {
            $i = 0;
            $inKey = 0;
            $max = count($params) - 1;
            $sql = ' WHERE ';

            foreach ($params as $key => $value) {
                switch ($type) {
                    case 'OR':
                        $prepareKey = $i;
                        break;

                    default:
                    case 'AND':
                        $prepareKey = 'or' . $i;
                        break;
                }

                $key = '`' . str_replace('.', '`.`', $key) . '`';

                if (is_null($value)) {
                    $sql .= $key . ' IS NULL ';
                } else {
                    if (!is_array($value)) {
                        $last = mb_substr($value, -1);
                        $first = mb_substr($value, 0, 1);

                        if ($first === '`' && $last === '`') {
                            $value = str_replace('.', '`.`', $value);
                        } else {
                            $prepare['wherev' . $prepareKey] = $value;

                            $value = ':wherev' . $prepareKey;
                        }

                        $sql .= $key . ' = ' . $value;
                    } elseif (
                        isset($value['type'])
                        && ($value['type'] == '<'
                            || $value['type'] == '>'
                            || $value['type'] == '<='
                            || $value['type'] == '>=')
                    ) {
                        $prepare['wherev' . $prepareKey] = $value['value'];

                        $sql .= $key . ' ' . $value['type'] . ' :wherev' . $prepareKey;
                    } elseif (isset($value['type']) && $value['type'] == 'NOT') {
                        if (is_null($value['value'])) {
                            $sql .= $key . ' IS NOT NULL ';
                        } else {
                            $prepare['wherev' . $prepareKey] = $value['value'];

                            $sql .= $key . ' != :wherev' . $prepareKey;
                        }
                    } elseif (isset($value['type']) && $value['type'] == 'REGEXP') {
                        $sql .= $key . ' REGEXP :wherev' . $prepareKey;

                        $prepare['wherev' . $prepareKey] = $value['value'];
                    } elseif (isset($value['type']) && $value['type'] == 'IN') {
                        $sql .= $key . ' IN (';

                        if (!is_array($value['value'])) {
                            $prepare['in' . $prepareKey] = $value['value'];

                            $sql .= ':in' . $prepareKey;
                        } else {
                            $bindKeys = [];

                            foreach ($value['value'] as $val) {
                                $bindKey = 'in' . $inKey++;
                                $prepare[$bindKey] = $val;
                                $bindKeys[] = ':' . $bindKey;
                            }

                            if (!empty($bindKeys)) {
                                $sql .= implode(', ', $bindKeys);
                            }
                        }

                        $sql .= ') ';
                    } elseif (isset($value['type']) && $value['type'] == 'NOT IN') {
                        $sql .= $key . ' NOT IN (';

                        if (!is_array($value['value'])) {
                            $prepare['notin' . $prepareKey] = $value['value'];

                            $sql .= ':notin' . $prepareKey;
                        } else {
                            $bindKeys = [];

                            foreach ($value['value'] as $val) {
                                $bindKey = 'notin' . $inKey++;
                                $prepare[$bindKey] = $val;
                                $bindKeys[] = ':' . $bindKey;
                            }

                            if (!empty($bindKeys)) {
                                $sql .= implode(', ', $bindKeys);
                            }
                        }

                        $sql .= ') ';
                    } else {
                        if (!isset($value['type'])) {
                            $value['type'] = '';
                        }

                        if (!isset($value['value'])) {
                            $value['value'] = '';
                        }

                        switch ($value['type']) {
                            case '%LIKE%':
                                $prepare['wherev' . $prepareKey] = '%' . $value['value'] . '%';

                                $sql .= $key . ' LIKE :wherev' . $prepareKey;
                                break;

                            case '%LIKE':
                                $prepare['wherev' . $prepareKey] = '%' . $value['value'];

                                $sql .= $key . ' LIKE :wherev' . $prepareKey;
                                break;

                            case 'LIKE%':
                                $prepare['wherev' . $prepareKey] = $value['value'] . '%';

                                $sql .= $key . ' LIKE :wherev' . $prepareKey;
                                break;

                            case 'NOT LIKE':
                                $prepare['wherev' . $prepareKey] = $value['value'];

                                $sql .= $key . ' NOT LIKE :wherev' . $prepareKey;
                                break;

                            case 'NOT %LIKE%':
                                $prepare['wherev' . $prepareKey] = '%' . $value['value'] . '%';

                                $sql .= $key . ' NOT LIKE :wherev' . $prepareKey;
                                break;

                            case 'NOT %LIKE':
                                $prepare['wherev' . $prepareKey] = '%' . $value['value'];

                                $sql .= $key . ' NOT LIKE :wherev' . $prepareKey;
                                break;

                            case 'NOT LIKE%':
                                $prepare['wherev' . $prepareKey] = $value['value'] . '%';

                                $sql .= $key . ' NOT LIKE :wherev' . $prepareKey;
                                break;

                            default:
                            case 'LIKE':
                                $prepare['wherev' . $prepareKey] = $value['value'];

                                $sql .= $key . ' LIKE :wherev' . $prepareKey;
                                break;
                        }
                    }
                }

                if ($max > $i) {
                    $sql .= ' ' . $type . ' ';
                }

                $i++;
            }
        }

        return [
            'where' => $sql,
            'prepare' => $prepare
        ];
    }

    /**
     * Where Statement with OR
     *
     * @param array $params
     *
     * @return array
     */
    public static function createQueryWhereOr(array $params): array
    {
        return self::createQueryWhere($params, 'OR');
    }

    /**
     * SET Query
     *
     * @param array|string $params
     * @param boolean|string $driver - deprecated
     *
     * @return array
     */
    public static function createQuerySet(array|string $params, bool|string $driver = false): array
    {
        if (is_string($params)) {
            return [
                'set' => ' SET ' . $params,
                'prepare' => []
            ];
        }

        // SQLITE
        /*
        if ( $driver == 'sqlite' )
        {
            $sql = ' SET ';
            $max = count( $params ) - 1;

            $fields = array();
            $values = array();

            $i = 0;

            $sql .= ' (';

            foreach ( $params as $key => $value )
            {
                $sql .= '`'. $key .'`';

                if ( $max > $i ) {
                    $sql .= ', ';
                }

                $values[] = ':v'. $i;

                $prepare['v'. $i] = $value;

                $i++;
            }

            $sql .= ') VALUES ('. implode( ',', $values ) .')';

            return array(
                'set'     => $sql,
                'prepare' => $prepare
            );
        }
        */
        // Standard SQL
        $prepare = [];
        $sql = '';

        if (is_array($params)) {
            $i = 0;
            $max = count($params) - 1;
            $sql = ' SET ';

            foreach ($params as $key => $value) {
                $sql .= '`' . $key . '` = :setv' . $i;

                $prepare['setv' . $i] = $value;

                if ($max > $i) {
                    $sql .= ', ';
                }

                $i++;
            }
        }

        return [
            'set' => $sql,
            'prepare' => $prepare
        ];
    }

    /**
     * The insert for SQLite
     *
     * @param array $params - the set params
     *
     * @return array
     * @deprecated
     */
    public static function createQuerySQLiteInsert(array $params): array
    {
        $set_params = $params['set'];

        $max = count($set_params) - 1;
        $i = 0;

        $prepare = [];
        $values = [];

        $sql = self::createQueryInsert($params['insert']);
        $sql .= ' (';

        foreach ($set_params as $key => $value) {
            $sql .= '`' . $key . '`';

            if ($max > $i) {
                $sql .= ', ';
            }

            $values[] = ':v' . $i;

            $prepare['v' . $i] = $value;

            $i++;
        }

        $sql .= ') VALUES (' . implode(',', $values) . ')';

        return [
            'insert' => $sql,
            'prepare' => $prepare
        ];
    }

    /**
     * Order Query
     *
     * @param array|string $params
     *
     * 'order' => 'field DESC'
     *
     * 'order' => ['field', 'field']
     *
     * 'order' => [
     *      'field'    => 'fieldName'
     *      'sort'     => 'DESC',
     *      'function' => 'RAND'
     * ]
     *
     * 'order' => [
     *      [
     *          'field'    => 'fieldName'
     *          'sort'     => 'DESC',
     *          'function' => 'RAND'
     *      ],
     *      [
     *          'field'    => 'fieldName'
     *          'sort'     => 'DESC',
     *          'function' => 'RAND'
     *      ]
     * ]
     *
     * @return string
     */
    public static function createQueryOrder(array|string $params): string
    {
        if (empty($params)) {
            return '';
        }

        // string
        if (is_string($params)) {
            $query = [];

            $params = trim($params, ',');
            $params = trim($params);
            $params = explode(',', $params);

            foreach ($params as $value) {
                $value = trim($value);
                $asc = strtolower(mb_substr($value, -3)) === 'asc';
                $desc = strtolower(mb_substr($value, -4)) === 'desc';

                if ($asc === false && $desc === false) {
                    $query[] = Orthos::cleanupDatabaseFieldName($value);
                    continue;
                }

                if ($asc !== false) {
                    $query[] = Orthos::cleanupDatabaseFieldName(mb_substr($value, 0, -3)) . ' ASC';
                    continue;
                }

                $query[] = Orthos::cleanupDatabaseFieldName(mb_substr($value, 0, -4)) . ' DESC';
            }

            return ' ORDER BY ' . implode(',', $query);
        }

        // order function stuff
        if (isset($params['field']) || isset($params['function']) || isset($params['sort'])) {
            $query = self::createQueryOrderFromArray($params);

            if (!$query) {
                return '';
            }

            return ' ORDER BY ' . $query;
        }

        // order as array
        if (is_array($params)) {
            $query = [];

            foreach ($params as $key => $sort) {
                // order function stuff
                if (isset($sort['field']) || isset($sort['function']) || isset($sort['sort'])) {
                    $result = self::createQueryOrderFromArray($sort);

                    if ($result) {
                        $query[] = $result;
                    }

                    continue;
                }

                if (!is_string($key)) {
                    $query[] = Orthos::cleanupDatabaseFieldName($sort);
                    continue;
                }

                $sort = strtoupper($sort);

                switch ($sort) {
                    case 'ASC':
                    case 'DESC':
                        break;

                    default:
                        $sort = '';
                }

                $query[] = Orthos::cleanupDatabaseFieldName($key) . ' ' . $sort;
            }

            return ' ORDER BY ' . implode(',', $query);
        }

        return '';
    }

    /**
     * Return the sql query for an order array
     *
     * @param array $params
     *
     * 'order' => [
     *      'field'    => 'fieldName'
     *      'sort'     => 'DESC',
     *      'function' => 'RAND'
     * ]
     *
     * 'order' => [
     *      [
     *          'field'    => 'fieldName'
     *          'sort'     => 'DESC',
     *          'function' => 'RAND'
     *      ],
     *      [
     *          'field'    => 'fieldName'
     *          'sort'     => 'DESC',
     *          'function' => 'RAND'
     *      ]
     * ]
     *
     * @return string
     */
    protected static function createQueryOrderFromArray(array $params): string
    {
        if (!isset($params['field'])) {
            $params['field'] = '';
        }

        // order function stuff
        $sorting = '';
        $field = Orthos::cleanupDatabaseFieldName($params['field']);

        if (isset($params['sort'])) {
            $sorting = strtoupper($params['sort']);

            switch ($sorting) {
                case 'ASC':
                case 'DESC':
                    break;

                default:
                    $sorting = '';
            }
        }

        if (isset($params['function'])) {
            $function = $params['function'];
            $function = preg_replace('/[^0-9,a-zA-Z_]/i', '', $function);
            $function = trim($function);

            return $function . '(' . $field . ')' . ' ' . $sorting;
        }

        return $field . ' ' . $sorting;
    }

    /**
     * Group By Query
     *
     * @param $params
     * @return string
     */
    public static function createQueryGroupBy($params): string
    {
        if (empty($params)) {
            return '';
        }

        if (is_string($params)) {
            $sql = ' GROUP BY ';
            $query = [];

            $params = trim($params, ',');
            $params = trim($params);
            $params = explode(',', $params);

            foreach ($params as $value) {
                $value = trim($value);
                $query[] = Orthos::cleanupDatabaseFieldName($value);
            }

            return $sql . implode(',', $query);
        }

        if (is_array($params)) {
            $sql = ' GROUP BY ';
            $query = [];

            foreach ($params as $sort) {
                $query[] = Orthos::cleanupDatabaseFieldName($sort);
            }

            return $sql . implode(',', $query);
        }

        return '';
    }

    /**
     * Limit Query
     *
     * @param integer|string $params
     *
     * @return array
     */
    public static function createQueryLimit(int|string $params): array
    {
        $sql = ' LIMIT ';
        $prepare = [];

        if (!str_contains($params, ',')) {
            $limit1 = (int)trim($params);

            $prepare[':limit1'] = [$limit1, PDO::PARAM_INT];

            $sql .= ':limit1';
        } else {
            $limit = explode(',', $params);

            if (!isset($limit[0]) || !isset($limit[1])) {
                return [
                    'limit' => '',
                    'prepare' => $prepare
                ];
            }

            $limit1 = (int)trim($limit[0]);
            $limit2 = (int)trim($limit[1]);

            if ($limit1 < 0) {
                $limit1 = 0;
            }

            if ($limit2 < 0) {
                $limit2 = 0;
            }

            $prepare[':limit1'] = [$limit1, PDO::PARAM_INT];
            $prepare[':limit2'] = [$limit2, PDO::PARAM_INT];

            $sql .= ':limit1,:limit2';
        }

        return [
            'limit' => $sql,
            'prepare' => $prepare
        ];
    }

    /**
     * Is order clause valid?
     *
     * @param string $value
     * @param array $allowed - allowed fields
     *
     * @return bool
     */
    public static function isOrderValid(string $value, array $allowed = []): bool
    {
        $value = trim($value);

        if (!str_contains($value, ' ')) {
            if (in_array($value, $allowed, true)) {
                return true;
            }

            return false;
        }

        $value = explode(' ', $value);

        switch (strtoupper($value[1])) {
            case 'ASC':
            case 'DESC':
                break; // is allowed

            default:
                return false;
        }

        if (in_array($value[0], $allowed, true)) {
            return true;
        }

        return false;
    }

    /**
     * Is where clause valid?
     *
     * @param array $where - where clause
     * @param array $allowed - allowed fields
     *
     * @return bool
     */
    public static function isWhereValid(array $where, array $allowed = []): bool
    {
        $allowed = array_flip($allowed);

        foreach ($where as $key => $params) {
            if (isset($allowed[$key])) {
                return true;
            }
        }

        return true;
    }
}
