<?php

/**
 * This file contains the \QUI\Database\DB
 */

namespace QUI\Database;

use QUI;
use QUI\Utils\Security\Orthos;

/**
 * QUIQQER DataBase Layer
 *
 * @uses    PDO
 * @author  www.pcsg.de (Henning Leutz)
 * @package quiqqer/utils
 */
class DB extends QUI\QDOM
{
    /**
     * PDO Object
     *
     * @var \PDO
     */
    protected $PDO = null;

    /**
     * DBTable Object
     *
     * @var \QUI\Database\Tables
     */
    protected $Tables = null;

    /**
     * SQLite Flag
     *
     * @var boolean
     */
    protected $sqlite = false;

    /**
     * @var bool|string
     */
    protected $version = false;

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
    public function __construct($attributes = [])
    {
        // defaults
        $this->setAttribute('host', 'localhost');
        $this->setAttribute('driver', 'mysql');
        $this->setAttribute('options', [
            \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ]);

        if (isset($attributes['driver']) && empty($attributes['driver'])) {
            unset($attributes['driver']);
        }

        // Attributes
        $this->setAttributes($attributes);

        $this->PDO = $this->getNewPDO();
        $this->PDO->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        try {
            $Date   = new \DateTime();
            $offset = $Date->getOffset();

            $offsetHours   = \round(\abs($offset) / 3600);
            $offsetMinutes = \round((\abs($offset) - $offsetHours * 3600) / 60);

            $offsetString = ($offset < 0 ? '-' : '+');
            $offsetString .= (\strlen($offsetHours) < 2 ? '0' : '').$offsetHours;
            $offsetString .= ':';
            $offsetString .= (\strlen($offsetMinutes) < 2 ? '0' : '').$offsetMinutes;

            $this->PDO->exec("SET time_zone = '{$offsetString}'");
        } catch (\PDOException $Exception) {
            QUI\System\Log::addError($Exception->getMessage());
        }

        $this->Tables = new Tables($this);
    }

    /**
     * Return a new PDO Object
     * This Object generate a new database connection
     *
     * attention: please use getPDO() if you want not a new database connection
     *
     * @return \PDO
     * @throws Exception
     */
    public function getNewPDO()
    {
        if ($this->getAttribute('dsn') === false) {
            $dsn = $this->getAttribute('driver').
                   ':dbname='.$this->getAttribute('dbname').
                   ';host='.$this->getAttribute('host');

            if ($this->getAttribute('port')) {
                $dsn .= ';port='.$this->getAttribute('port');
            }

            $this->setAttribute('dsn', $dsn);
        }

        // sqlite PDO
        try {
            if ($this->getAttribute('driver') == 'sqlite') {
                $this->sqlite = true;

                return new \PDO(
                    'sqlite:'.$this->getAttribute('dbname')
                );
            }

            return new \PDO(
                $this->getAttribute('dsn'),
                $this->getAttribute('user'),
                $this->getAttribute('password'),
                $this->getAttribute('options')
            );
        } catch (\PDOException $Exception) {
            throw new QUI\Database\Exception(
                $Exception->getMessage(),
                $Exception->getCode()
            );
        }
    }

    /**
     * Return the internal PDO Object
     *
     * @return \PDO
     */
    public function getPDO()
    {
        return $this->PDO;
    }

    /**
     * Return the server version of the database
     *
     * @return bool|mixed|string
     */
    public function getVersion()
    {
        if (!$this->version) {
            $this->version = $this->PDO->query('select version()')->fetchColumn();

            \preg_match("/^[0-9\.]+/", $this->version, $match);

            $this->version = $match[0];
        }

        return $this->version;
    }

    /**
     * Database object for tables
     *
     * @return \QUI\Database\Tables
     */
    public function table()
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
    public function isSQLite()
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
    public function createQuery(array $params = [])
    {
        $query   = $this->createQuerySelect($params);
        $prepare = [];

        /**
         * Start Block
         */
        if (isset($params['insert']) && !empty($params)) {
            if ($this->isSQLite() && isset($params['set'])) {
                $insert = $this->createQuerySQLiteInsert($params);

                $query   = $insert['insert'];
                $prepare = array_merge($prepare, $insert['prepare']);

                unset($params['set']);
            } else {
                $query = $this->createQueryInsert($params['insert']);
            }
        }

        if (isset($params['replace']) && !empty($params['replace'])) {
            $query = $this->createQueryReplace($params['replace']);
        }

        if (isset($params['update']) && !empty($params['update'])) {
            $query = $this->createQueryUpdate($params['update']);
        }

        if (isset($params['count']) && !empty($params['count'])) {
            $query = $this->createQueryCount($params['count']);
        }

        if (isset($params['delete']) && $params['delete'] === true) {
            $query = $this->createQueryDelete();
        }

        /**
         * From Block
         */
        if (isset($params['from']) && !empty($params['from'])) {
            $query .= $this->createQueryFrom($params['from']);
        }

        /**
         * set & where Block
         */
        if (isset($params['set']) && !empty($params['set'])) {
            $set = $this->createQuerySet(
                $params['set'],
                $this->getAttribute('driver')
            );

            $query   .= $set['set'];
            $prepare = \array_merge($prepare, $set['prepare']);
        }

        if (isset($params['where']) && !empty($params['where'])) {
            $where = $this->createQueryWhere($params['where']);

            $query   .= $where['where'];
            $prepare = \array_merge($prepare, $where['prepare']);
        }

        if (isset($params['where_or']) && !empty($params['where_or'])) {
            $where = $this->createQueryWhereOr($params['where_or']);

            if (\strpos($query, 'WHERE') === false) {
                $query .= $where['where'];
            } else {
                $query .= ' AND ('.\str_replace('WHERE', '', $where['where']).')';
            }

            $prepare = \array_merge($prepare, $where['prepare']);
        }

        /**
         * Order Block
         */
        if (isset($params['order']) && !empty($params['order'])) {
            $query .= $this->createQueryOrder($params['order']);
        }

        if (isset($params['group']) && !empty($params['group'])) {
            $query .= $this->createQueryGroupBy($params['group']);
        }

        if (isset($params['limit']) && !empty($params['limit'])) {
            $limit = $this->createQueryLimit($params['limit']);

            $query   .= $limit['limit'];
            $prepare = \array_merge($prepare, $limit['prepare']);
        }

        // debuging
        if (isset($params['debug'])) {
            QUI\System\Log::writeRecursive([
                'query'   => $query,
                'prepare' => $prepare
            ]);
        }

        return [
            'query'   => $query,
            'prepare' => $prepare
        ];
    }

    /**
     * Execute query and returned a PDOStatement
     * (Prepare Statement)
     *
     * @param array $params (see at createQuery())
     *
     * @return \PDOStatement
     *
     * @throws QUI\Database\Exception
     */
    public function exec(array $params = [])
    {
        if (\class_exists('QUI') && QUI::$Events !== null) {
            try {
                QUI::getEvents()->fireEvent('dataBaseQueryCreate', [$this]);
            } catch (QUI\Exception $Exception) {
                throw new QUI\Database\Exception(
                    $Exception->getMessage(),
                    $Exception->getCode()
                );
            }
        }

        $start = \microtime();
        $query = $this->createQuery($params);


        if (\class_exists('QUI') && QUI::$Events !== null) {
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

        $Statement = $this->getPDO()->prepare($query['query'].';');

        foreach ($query['prepare'] as $key => $val) {
            if (\is_array($val) && isset($val[0])) {
                if (isset($val[1])) {
                    $Statement->bindValue($key, $val[0], $val[1]);
                } else {
                    $Statement->bindValue($key, $val[0], \PDO::PARAM_STR);
                }

                continue;
            }

            $Statement->bindValue($key, $val, \PDO::PARAM_STR);
        }

        try {
            $Statement->execute();
        } catch (\PDOException $Exception) {
            $message = $Exception->getMessage();
            $message .= \print_r($query, true);

            if (\class_exists('QUI') && QUI::$Events !== null) {
                try {
                    QUI::getEvents()->fireEvent(
                        'dataBaseQueryEnd',
                        [$this, $query, $start, \microtime()]
                    );

                    QUI::getEvents()->fireEvent(
                        'dataBaseQueryError',
                        [$this, $Exception, $query, $start, \microtime()]
                    );
                } catch (QUI\Exception $Exception) {
                    throw new QUI\Database\Exception(
                        $Exception->getMessage(),
                        $Exception->getCode()
                    );
                }
            }


            $Exception = new QUI\Database\Exception($message, $Exception->getCode());

            if (\class_exists('QUI\System\Log')) {
                QUI\System\Log::addError($Exception->getMessage());
                QUI\System\Log::writeDebugException($Exception);
            }

            throw $Exception;
        }

        if (\class_exists('QUI') && QUI::$Events !== null) {
            try {
                QUI::getEvents()->fireEvent('dataBaseQueryEnd', [$this, $query, $start, \microtime()]);
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
        $FETCH_STYLE = \PDO::FETCH_ASSOC
    ) {
        $Statement = $this->exec($params);

        switch ($FETCH_STYLE) {
            case \PDO::FETCH_ASSOC:
            case \PDO::FETCH_BOTH:
            case \PDO::FETCH_BOUND:
            case \PDO::FETCH_CLASS:
            case \PDO::FETCH_OBJ:
                break;

            default:
                $FETCH_STYLE = \PDO::FETCH_ASSOC;
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
     * @return \PDOStatement|false
     * @throws Exception
     *
     */
    public function execSQL($query)
    {
        $Statement = $this->getPDO()->prepare($query);

        try {
            $Statement->execute();
        } catch (\PDOException $Exception) {
            $message = $Exception->getMessage();
            $message .= \print_r($query, true);

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
    public function fetchSQL($query, $FETCH_STYLE = \PDO::FETCH_ASSOC)
    {
        $Statement = $this->execSQL($query);

        switch ($FETCH_STYLE) {
            case \PDO::FETCH_ASSOC:
            case \PDO::FETCH_BOTH:
            case \PDO::FETCH_BOUND:
            case \PDO::FETCH_CLASS:
            case \PDO::FETCH_OBJ:
                break;

            default:
                $FETCH_STYLE = \PDO::FETCH_ASSOC;
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
     * @return \PDOStatement
     * @throws QUI\Database\Exception
     *
     */
    public function update($table, $data, $where)
    {
        return $this->exec([
            'update' => $table,
            'set'    => $data,
            'where'  => $where
        ]);
    }

    /**
     * Insert a record
     *
     * @param string $table
     * @param array $data
     *
     * @return \PDOStatement
     * @throws QUI\Database\Exception
     *
     */
    public function insert($table, $data)
    {
        return $this->exec([
            'insert' => $table,
            'set'    => $data
        ]);
    }

    /**
     * If a dataset's primary/unique already exists, the old dataset is replaced.
     * If the dataset does not exist yet, the dataset is inserted.
     *
     * @param string $table
     * @param array $data
     *
     * @return \PDOStatement
     * @throws QUI\Database\Exception
     *
     */
    public function replace($table, $data)
    {
        return $this->exec([
            'replace' => $table,
            'set'     => $data
        ]);
    }

    /**
     * Deletes a record
     *
     * @param string $table - Name of the Database Table
     * @param array $where - data field, where statement
     *
     * @return \PDOStatement
     * @throws QUI\Database\Exception
     *
     */
    public function delete($table, $where)
    {
        return $this->exec([
            'delete' => true,
            'from'   => $table,
            'where'  => $where
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
    public static function createQuerySelect($params)
    {
        if (!isset($params['select']) || empty($params['select'])) {
            return 'SELECT * ';
        }

        if (\is_string($params['select'])) {
            $params['select'] = \explode(',', $params['select']);
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

            if (!\is_array($select)) {
                $params['select'][$key] = Orthos::cleanupDatabaseFieldName($select);
                continue;
            }

            if (!isset($select['field']) || !isset($select['function'])) {
                continue;
            }

            $fields = $select['field'];

            if (!\is_array($fields)) {
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
            $function = \preg_replace('/[^0-9,a-zA-Z_]/i', '', $function);
            $function = \trim($function);

            $functionParams = \implode(',', $fields);

            $params['select'][$key] = $function.'('.$functionParams.')';
        }

        return 'SELECT '.\implode(', ', $params['select']).' ';
    }

    /**
     * Insert Query
     *
     * @param string $params
     *
     * @return string
     */
    public static function createQueryInsert($params)
    {
        return 'INSERT INTO '.Orthos::cleanupDatabaseFieldName($params);
    }

    /**
     * Replace Query
     *
     * @param string $params
     *
     * @return string
     */
    public static function createQueryReplace($params)
    {
        return 'REPLACE INTO '.Orthos::cleanupDatabaseFieldName($params);
    }

    /**
     * Update Query
     *
     * @param string $params
     *
     * @return string
     */
    public static function createQueryUpdate($params)
    {
        return 'UPDATE '.Orthos::cleanupDatabaseFieldName($params);
    }

    /**
     * Delete Query
     *
     * @return string
     */
    public static function createQueryDelete()
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
    public static function createQueryCount($params)
    {
        if (\is_array($params) && isset($params['select'])) {
            $query = ' SELECT COUNT(';
            $query .= Orthos::cleanupDatabaseFieldName($params['select']);
            $query .= ') ';

            if (isset($params['as'])) {
                $query .= 'AS '.Orthos::cleanupDatabaseFieldName($params['as']);
            }

            return $query;
        }

        $query = ' SELECT COUNT(*) ';

        if (\is_string($params)) {
            $query .= 'AS '.Orthos::cleanupDatabaseFieldName($params);
        }

        return $query;
    }

    /**
     * FROM Query
     *
     * @param string|array $params
     *
     * @return string
     */
    public static function createQueryFrom($params)
    {
        if (\is_string($params)) {
            return ' FROM '.Orthos::cleanupDatabaseFieldName($params);
        }

        if (\is_array($params)) {
            $from = \array_unique($params);

            foreach ($from as $key => $entry) {
                $from[$key] = Orthos::cleanupDatabaseFieldName($entry);
            }

            return ' FROM '.\implode(',', $from);
        }

        return '';
    }

    /**
     * WHERE Query
     *
     * @param string|array $params
     * @param string $type - if more than one where, you can specific the where typ (OR, AND)
     *
     * @return array array(
     *     'where' => 'WHERE param = :param',
     *     'prepare' => array(
     *         'param' => value
     *     )
     * )
     */
    public static function createQueryWhere($params, $type = 'AND')
    {
        if (\is_string($params)) {
            return [
                'where'   => ' WHERE '.$params,
                'prepare' => []
            ];
        }

        $prepare = [];
        $sql     = '';

        if (\is_array($params)) {
            $i   = 0;
            $max = \count($params) - 1;

            $sql     = ' WHERE ';
            $prepare = [];

            foreach ($params as $key => $value) {
                switch ($type) {
                    case 'OR':
                        $prepareKey = $i;
                        break;

                    default:
                    case 'AND':
                        $prepareKey = 'or'.$i;
                        break;
                }

                $key = '`'.\str_replace('.', '`.`', $key).'`';

                if (\is_null($value)) {
                    $sql .= $key.' IS NULL ';
                } else {
                    if (!\is_array($value)) {
                        if (\strpos($value, '`') !== false) {
                            $value = \str_replace('.', '`.`', $value);
                        } else {
                            $prepare['wherev'.$prepareKey] = $value;

                            $value = ':wherev'.$prepareKey;
                        }

                        $sql .= $key.' = '.$value;
                    } elseif (isset($value['type'])
                              && ($value['type'] == '<'
                                  || $value['type'] == '>'
                                  || $value['type'] == '<='
                                  || $value['type'] == '>=')
                    ) {
                        $prepare['wherev'.$prepareKey] = $value['value'];

                        $sql .= $key.' '.$value['type'].' :wherev'.$prepareKey;
                    } elseif (isset($value['type']) && $value['type'] == 'NOT') {
                        if (\is_null($value['value'])) {
                            $sql .= $key.' IS NOT NULL ';
                        } else {
                            $prepare['wherev'.$prepareKey] = $value['value'];

                            $sql .= $key.' != :wherev'.$prepareKey;
                        }
                    } elseif (isset($value['type']) && $value['type'] == 'REGEXP') {
                        $sql .= $key.' REGEXP :wherev'.$prepareKey;

                        $prepare['wherev'.$prepareKey] = $value['value'];
                    } elseif (isset($value['type']) && $value['type'] == 'IN') {
                        $sql .= $key.' IN (';

                        if (!\is_array($value['value'])) {
                            $prepare['in'.$prepareKey] = $value['value'];

                            $sql .= ':in'.$prepareKey;
                        } else {
                            $in       = $i;
                            $bindKeys = [];

                            foreach ($value['value'] as $val) {
                                $bindKey           = 'in'.$in;
                                $prepare[$bindKey] = $val;
                                $bindKeys[]        = ':'.$bindKey;

                                $in++;
                            }

                            if (!empty($bindKeys)) {
                                $sql .= \implode(', ', $bindKeys);
                            }
                        }

                        $sql .= ') ';
                    } elseif (isset($value['type']) && $value['type'] == 'NOT IN') {
                        $sql .= $key.' NOT IN (';

                        if (!\is_array($value['value'])) {
                            $prepare['notin'.$prepareKey] = $value['value'];

                            $sql .= ':notin'.$prepareKey;
                        } else {
                            $in       = $i;
                            $bindKeys = [];

                            foreach ($value['value'] as $val) {
                                $bindKey           = 'notin'.$in;
                                $prepare[$bindKey] = $val;
                                $bindKeys[]        = ':'.$bindKey;

                                $in++;
                            }

                            if (!empty($bindKeys)) {
                                $sql .= \implode(', ', $bindKeys);
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
                                $prepare['wherev'.$prepareKey] = '%'.$value['value'].'%';

                                $sql .= $key.' LIKE :wherev'.$prepareKey;
                                break;

                            case '%LIKE':
                                $prepare['wherev'.$prepareKey] = '%'.$value['value'];

                                $sql .= $key.' LIKE :wherev'.$prepareKey;
                                break;

                            case 'LIKE%':
                                $prepare['wherev'.$prepareKey] = $value['value'].'%';

                                $sql .= $key.' LIKE :wherev'.$prepareKey;
                                break;

                            case 'NOT LIKE':
                                $prepare['wherev'.$prepareKey] = $value['value'];

                                $sql .= $key.' NOT LIKE :wherev'.$prepareKey;
                                break;

                            case 'NOT %LIKE%':
                                $prepare['wherev'.$prepareKey] = '%'.$value['value'].'%';

                                $sql .= $key.' NOT LIKE :wherev'.$prepareKey;
                                break;

                            case 'NOT %LIKE':
                                $prepare['wherev'.$prepareKey] = '%'.$value['value'];

                                $sql .= $key.' NOT LIKE :wherev'.$prepareKey;
                                break;

                            case 'NOT LIKE%':
                                $prepare['wherev'.$prepareKey] = $value['value'].'%';

                                $sql .= $key.' NOT LIKE :wherev'.$prepareKey;
                                break;

                            default:
                            case 'LIKE':
                                $prepare['wherev'.$prepareKey] = $value['value'];

                                $sql .= $key.' LIKE :wherev'.$prepareKey;
                                break;
                        }
                    }
                }

                if ($max > $i) {
                    $sql .= ' '.$type.' ';
                }

                $i++;
            }
        }

        return [
            'where'   => $sql,
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
    public static function createQueryWhereOr($params)
    {
        return self::createQueryWhere($params, 'OR');
    }

    /**
     * SET Query
     *
     * @param string|array $params
     * @param string|boolean $driver - deprecated
     *
     * @return array
     */
    public static function createQuerySet($params, $driver = false)
    {
        if (\is_string($params)) {
            return [
                'set'     => ' SET '.$params,
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
        $sql     = '';

        if (\is_array($params)) {
            $i   = 0;
            $max = \count($params) - 1;

            $sql     = ' SET ';
            $prepare = [];

            foreach ($params as $key => $value) {
                $sql .= '`'.$key.'` = :setv'.$i;

                $prepare['setv'.$i] = $value;

                if ($max > $i) {
                    $sql .= ', ';
                }

                $i++;
            }
        }

        return [
            'set'     => $sql,
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
    public static function createQuerySQLiteInsert($params)
    {
        $set_params = $params['set'];

        $max = \count($set_params) - 1;
        $i   = 0;

        $prepare = [];
        $values  = [];

        $sql = self::createQueryInsert($params['insert']);
        $sql .= ' (';

        foreach ($set_params as $key => $value) {
            $sql .= '`'.$key.'`';

            if ($max > $i) {
                $sql .= ', ';
            }

            $values[] = ':v'.$i;

            $prepare['v'.$i] = $value;

            $i++;
        }

        $sql .= ') VALUES ('.\implode(',', $values).')';

        return [
            'insert'  => $sql,
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
    public static function createQueryOrder($params)
    {
        if (empty($params)) {
            return '';
        }

        // string
        if (\is_string($params)) {
            $query = [];

            $params = \trim($params, ',');
            $params = \trim($params);
            $params = \explode(',', $params);

            foreach ($params as $key => $value) {
                $value = \trim($value);
                $asc   = \strtolower(\mb_substr($value, -3)) === 'asc';
                $desc  = \strtolower(\mb_substr($value, -4)) === 'desc';

                if ($asc === false && $desc === false) {
                    $query[] = Orthos::cleanupDatabaseFieldName($value);
                    continue;
                }

                if ($asc !== false) {
                    $query[] = Orthos::cleanupDatabaseFieldName(\mb_substr($value, 0, -3)).' ASC';
                    continue;
                }

                $query[] = Orthos::cleanupDatabaseFieldName(\mb_substr($value, 0, -4)).' DESC';
            }

            return ' ORDER BY '.\implode(',', $query);
        }

        // order function stuff
        if (isset($params['field']) || isset($params['function']) || isset($params['sort'])) {
            $query = self::createQueryOrderFromArray($params);

            if (!$query) {
                return '';
            }

            return ' ORDER BY '.$query;
        }

        // order as array
        if (\is_array($params)) {
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

                if (!\is_string($key)) {
                    $query[] = Orthos::cleanupDatabaseFieldName($sort);
                    continue;
                }

                $sort = \strtoupper($sort);

                switch ($sort) {
                    case 'ASC':
                    case 'DESC':
                        break;

                    default:
                        $sort = '';
                }

                $query[] = Orthos::cleanupDatabaseFieldName($key).' '.$sort;
            }

            return ' ORDER BY '.\implode(',', $query);
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
     * @return bool|string
     */
    protected static function createQueryOrderFromArray($params)
    {
        if (!isset($params['field'])) {
            $params['field'] = '';
        }

        // order function stuff
        $sorting = '';
        $field   = Orthos::cleanupDatabaseFieldName($params['field']);

        if (isset($params['sort'])) {
            $sorting = \strtoupper($params['sort']);

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
            $function = \preg_replace('/[^0-9,a-zA-Z_]/i', '', $function);
            $function = \trim($function);

            return $function.'('.$field.')'.' '.$sorting;
        }

        return $field.' '.$sorting;
    }

    /**
     * Group By Query
     *
     * @param $params
     * @return string
     */
    public static function createQueryGroupBy($params)
    {
        if (empty($params)) {
            return '';
        }

        if (\is_string($params)) {
            $sql   = ' GROUP BY ';
            $query = [];

            $params = \trim($params, ',');
            $params = \trim($params);
            $params = \explode(',', $params);

            foreach ($params as $key => $value) {
                $value   = \trim($value);
                $query[] = Orthos::cleanupDatabaseFieldName($value);
            }

            return $sql.\implode(',', $query);
        }

        if (\is_array($params)) {
            $sql   = ' GROUP BY ';
            $query = [];

            foreach ($params as $key => $sort) {
                $query[] = Orthos::cleanupDatabaseFieldName($sort);
            }

            return $sql.\implode(',', $query);
        }

        return '';
    }

    /**
     * Limit Query
     *
     * @param string|integer $params
     *
     * @return array
     */
    public static function createQueryLimit($params)
    {
        $sql     = ' LIMIT ';
        $prepare = [];

        if (\strpos($params, ',') === false) {
            $limit1 = (int)\trim($params);

            $prepare[':limit1'] = [$limit1, \PDO::PARAM_INT];

            $sql .= ':limit1';
        } else {
            $limit = \explode(',', $params);

            if (!isset($limit[0]) || !isset($limit[1])) {
                return [
                    'limit'   => '',
                    'prepare' => $prepare
                ];
            }

            $limit1 = (int)\trim($limit[0]);
            $limit2 = (int)\trim($limit[1]);

            if ($limit1 < 0) {
                $limit1 = 0;
            }

            if ($limit2 < 0) {
                $limit2 = 0;
            }

            $prepare[':limit1'] = [$limit1, \PDO::PARAM_INT];
            $prepare[':limit2'] = [$limit2, \PDO::PARAM_INT];

            $sql .= ':limit1,:limit2';
        }

        return [
            'limit'   => $sql,
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
    public static function isOrderValid($value, $allowed = [])
    {
        if (!\is_string($value)) {
            return false;
        }

        $value = \trim($value);

        if (\strpos($value, ' ') === false) {
            foreach ($allowed as $field) {
                if ($value === $field) {
                    return true;
                }
            }

            return false;
        }

        $value = \explode(' ', $value);

        switch (\strtoupper($value[1])) {
            case 'ASC':
            case 'DESC':
                break; // is allowed

            default:
                return false;
        }

        foreach ($allowed as $field) {
            if ($value[0] === $field) {
                return true;
            }
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
    public static function isWhereValid(array $where, $allowed = [])
    {
        $allowed = \array_flip($allowed);

        foreach ($where as $key => $params) {
            if (isset($allowed[$key])) {
                return true;
            }
        }

        return true;
    }
}
