<?php

/**
 * This file contains the \QUI\Database\DB
 */

namespace QUI\Database;

use QUI;

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
     * Constructor
     *
     * @param array $attributes
     * - host
     * - user
     * - password
     * - dbname
     * - options (optional)
     * - driver (optional)
     */
    public function __construct($attributes = array())
    {
        // defaults
        $this->setAttribute('host', 'localhost');
        $this->setAttribute('driver', 'mysql');
        $this->setAttribute('options', array(
            \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
        ));

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

            $offsetHours   = round(abs($offset) / 3600);
            $offsetMinutes = round((abs($offset) - $offsetHours * 3600) / 60);
            $offsetString  = ($offset < 0 ? '-' : '+');
            $offsetString .= (strlen($offsetHours) < 2 ? '0' : '') . $offsetHours;
            $offsetString .= ':';
            $offsetString .= (strlen($offsetMinutes) < 2 ? '0' : '')
                             . $offsetMinutes;

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
            $this->setAttribute(
                'dsn',
                $this->getAttribute('driver') .
                ':dbname=' . $this->getAttribute('dbname') .
                ';host=' . $this->getAttribute('host')
            );
        }

        // sqlite PDO
        try {
            if ($this->getAttribute('driver') == 'sqlite') {
                $this->sqlite = true;

                return new \PDO(
                    'sqlite:' . $this->getAttribute('dbname')
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
     * PDO Objekt bekommen
     *
     * @return \PDO
     */
    public function getPDO()
    {
        return $this->PDO;
    }

    /**
     * Datenbank Objekt für Tabellen
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
     * Ein Query String erstellen
     *
     * @param array $params
     *        array(
     *        'insert' => 'table'
     *        'update' => 'table'
     *        'delete' => 'table'
     *        'count'  => 'field'
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
     *        'prepare' => array() - Prepared Statemanet Vars
     *    )
     */
    public function createQuery(array $params = array())
    {
        $query   = $this->createQuerySelect($params);
        $prepare = array();

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

            $query .= $set['set'];
            $prepare = array_merge($prepare, $set['prepare']);
        }

        if (isset($params['where']) && !empty($params['where'])) {
            $where = $this->createQueryWhere($params['where']);

            $query .= $where['where'];
            $prepare = array_merge($prepare, $where['prepare']);
        }

        if (isset($params['where_or']) && !empty($params['where_or'])) {
            $where = $this->createQueryWhereOr($params['where_or']);

            if (strpos($query, 'WHERE') === false) {
                $query .= $where['where'];
            } else {
                $query .= ' AND (' . str_replace('WHERE', '', $where['where'])
                          . ')';
            }

            $prepare = array_merge($prepare, $where['prepare']);
        }

        /**
         * Order Block
         */
        if (isset($params['order']) && !empty($params['order'])) {
            $query .= $this->createQueryOrder($params['order']);
        }

        if (isset($params['group']) && !empty($params['group'])) {
            $query .= ' GROUP BY ' . $params['group'];
        }

        if (isset($params['limit']) && !empty($params['limit'])) {
            $limit = $this->createQueryLimit($params['limit']);

            $query .= $limit['limit'];
            $prepare = array_merge($prepare, $limit['prepare']);
        }

        // debuging
        if (isset($params['debug'])) {
            QUI\System\Log::writeRecursive(array(
                'query'   => $query,
                'prepare' => $prepare
            ));
        }

        return array(
            'query'   => $query,
            'prepare' => $prepare
        );
    }

    /**
     * Query ausführen und ein PDOStatement bekommen
     * (Prepare Statement)
     *
     * @param array $params (see at createQuery())
     *
     * @return \PDOStatement
     * @throws QUI\Database\Exception
     */
    public function exec(array $params = array())
    {
        $query = $this->createQuery($params);

        if (isset($params['debug'])) {
            QUI\System\Log::writeRecursive($query);
        }

        $Statement = $this->getPDO()->prepare($query['query'] . ';');

        foreach ($query['prepare'] as $key => $val) {
            if (is_array($val) && isset($val[0])) {
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
            $message .= print_r($query, true);

            throw new QUI\Database\Exception($message, $Exception->getCode());
        }

        return $Statement;
    }

    /**
     * Query ausführen und als die Ergebnisse bekommen
     *
     * @param array $params (see at createQuery())
     * @param integer $FETCH_STYLE - \PDO::FETCH*
     *
     * @return array
     */
    public function fetch(
        array $params = array(),
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

        return $Statement->fetchAll($FETCH_STYLE);
    }

    /**
     * Query ausführen und als Ergebnisse bekommen
     * Das Query wird direkt überrgeben!
     * Besser ->fetch() nutzen und die Parameter als Array übergeben
     *
     * @param string $query
     * @param integer $FETCH_STYLE - \PDO::FETCH*
     *
     * @throws QUI\Database\Exception
     *
     * @return array
     */
    public function fetchSQL($query, $FETCH_STYLE = \PDO::FETCH_ASSOC)
    {
        $Statement = $this->getPDO()->prepare($query);

        try {
            $Statement->execute();
        } catch (\PDOException $Exception) {
            $message = $Exception->getMessage();
            $message .= print_r($query, true);

            throw new QUI\Database\Exception($message, $Exception->getCode());
        }

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
     * Aktualisiert einen Datensatz
     *
     * @param string $table
     * @param array $data
     * @param array $where
     *
     * @return \PDOStatement
     */
    public function update($table, $data, $where)
    {
        return $this->exec(array(
            'update' => $table,
            'set'    => $data,
            'where'  => $where
        ));
    }

    /**
     * Aktualisiert einen Datensatz
     *
     * @param string $table
     * @param array $data
     *
     * @return \PDOStatement
     */
    public function insert($table, $data)
    {
        return $this->exec(array(
            'insert' => $table,
            'set'    => $data
        ));
    }

    /**
     * Löscht einen Datensatz
     *
     * @param string $table - Name of the Database Table
     * @param array $where - data field, where statement
     *
     * @return \PDOStatement
     */
    public function delete($table, $where)
    {
        return $this->exec(array(
            'delete' => true,
            'from'   => $table,
            'where'  => $where
        ));
    }

    /**
     * SELECT Query Abschnitt
     *
     * @param array $params
     *
     * @return string
     */
    public static function createQuerySelect($params)
    {
        if (!isset($params['select']) || empty($params['select'])) {
            return 'SELECT * ';
        }

        if (is_array($params['select'])) {
            return 'SELECT ' . implode(',', $params['select']) . ' ';
        }

        return 'SELECT ' . $params['select'] . ' ';
    }

    /**
     * Insert Query Abschnitt
     *
     * @param string $params
     *
     * @return string
     */
    public static function createQueryInsert($params)
    {
        return 'INSERT INTO `' . $params . '` ';
    }

    /**
     * Update Query Abschnitt
     *
     * @param string $params
     *
     * @return string
     */
    public static function createQueryUpdate($params)
    {
        return 'UPDATE `' . $params . '` ';
    }

    /**
     * Delete Query Abschnitt
     *
     * @return string
     */
    public static function createQueryDelete()
    {
        return 'DELETE ';
    }

    /**
     * COUNT() Query Abschnitt
     *
     * @param array|string $params
     *
     * @return string
     */
    public static function createQueryCount($params)
    {
        if (is_array($params) && isset($params['select'])) {
            $query = ' SELECT COUNT(' . $params['select'] . ') ';

            if (isset($params['as'])) {
                $query .= 'AS ' . $params['as'] . ' ';
            }

            return $query;
        }

        $query = ' SELECT COUNT(*) ';

        if (is_string($params)) {
            $query .= 'AS ' . $params . ' ';
        }

        return $query;
    }

    /**
     * FROM Query Abschnitt
     *
     * @param string|array $params
     *
     * @return string
     */
    public static function createQueryFrom($params)
    {
        if (is_string($params)) {
            return ' FROM `' . $params . '` ';
        }

        $sql = '';

        if (is_array($params)) {
            $sql  = ' FROM ';
            $from = implode('`', array_unique($params));
            $from = '`' . str_replace('`', '`,`', $from) . '`';

            $sql .= $from;
        }

        return $sql;
    }

    /**
     * WHERE Query Abschnitt
     *
     * @param string|array $params
     * @param string $type - if more than one where, you can specific the where typ (OR, AND)
     *
     * @return array array(
     *        'where' => 'WHERE param = :param',
     *      'prepare' => array(
     *          'param' => value
     *      )
     * )
     */
    public static function createQueryWhere($params, $type = 'AND')
    {
        if (is_string($params)) {
            return array(
                'where'   => ' WHERE ' . $params,
                'prepare' => array()
            );
        }

        $prepare = array();
        $sql     = '';

        if (is_array($params)) {
            $i   = 0;
            $max = count($params) - 1;

            $sql     = ' WHERE ';
            $prepare = array();

            foreach ($params as $key => $value) {
                $key = '`' . str_replace('.', '`.`', $key) . '`';

                if (is_null($value)) {
                    $sql .= $key . ' IS NULL ';
                } else {
                    if (!is_array($value)) {
                        if (strpos($value, '`') !== false) {
                            $value = str_replace('.', '`.`', $value);
                        } else {
                            $prepare['wherev' . $i] = $value;
                            $value                  = ':wherev' . $i;
                        }

                        $sql .= $key . ' = ' . $value;
                    } elseif (isset($value['type'])
                              && ($value['type'] == '<'
                                  || $value['type'] == '>'
                                  || $value['type'] == '<='
                                  || $value['type'] == '>=')
                    ) {
                        $prepare['wherev' . $i] = $value['value'];
                        $sql .= $key . ' ' . $value['type'] . ' :wherev' . $i;
                    } elseif (isset($value['type']) && $value['type'] == 'NOT') {
                        if (is_null($value['value'])) {
                            $sql .= $key . ' IS NOT NULL ';
                        } else {
                            $prepare['wherev' . $i] = $value['value'];
                            $sql .= $key . ' != :wherev' . $i;
                        }
                    } elseif (isset($value['type']) && $value['type'] == 'REGEXP') {
                        $sql .= $key . ' REGEXP :wherev' . $i;
                        $prepare['wherev' . $i] = $value['value'];
                    } elseif (isset($value['type']) && $value['type'] == 'IN') {
                        $sql .= $key . ' IN (';

                        if (!is_array($value['value'])) {
                            $prepare['in' . $i] = $value['value'];
                            $sql .= ':in' . $i;
                        } else {
                            $in = 0;

                            foreach ($value['value'] as $val) {
                                $prepare['in' . $in] = $val;

                                if ($in != 0) {
                                    $sql .= ', ';
                                }

                                $sql .= ':in' . $in;
                                $in++;
                            }
                        }

                        $sql .= ') ';
                    } elseif (isset($value['type']) && $value['type'] == 'NOT IN') {
                        $sql .= $key . ' NOT IN (';

                        if (!is_array($value['value'])) {
                            $prepare['notin' . $i] = $value['value'];
                            $sql .= ':notin' . $i;
                        } else {
                            $in = 0;

                            foreach ($value['value'] as $val) {
                                $prepare['notin' . $in] = $val;

                                if ($in != 0) {
                                    $sql .= ', ';
                                }

                                $sql .= ':notin' . $in;
                                $in++;
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
                                $prepare['wherev' . $i] = '%' . $value['value'] . '%';
                                break;

                            case '%LIKE':
                                $prepare['wherev' . $i] = '%' . $value['value'];
                                break;

                            case 'LIKE%':
                                $prepare['wherev' . $i] = $value['value'] . '%';
                                break;

                            default:
                            case 'LIKE':
                                $prepare['wherev' . $i] = $value['value'];
                                break;
                        }

                        $sql .= $key . ' LIKE :wherev' . $i;
                    }
                }

                if ($max > $i) {
                    $sql .= ' ' . $type . ' ';
                }

                $i++;
            }
        }

        return array(
            'where'   => $sql,
            'prepare' => $prepare
        );
    }

    /**
     * Where Statement als OR
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
     * SET Query Abschnitt
     *
     * @param string|array $params
     * @param string|boolean $driver - depricated
     *
     * @return array
     */
    public static function createQuerySet($params, $driver = false)
    {
        if (is_string($params)) {
            return array(
                'set'     => ' SET ' . $params,
                'prepare' => array()
            );
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
        $prepare = array();
        $sql     = '';

        if (is_array($params)) {
            $i   = 0;
            $max = count($params) - 1;

            $sql     = ' SET ';
            $prepare = array();

            foreach ($params as $key => $value) {
                $sql .= '`' . $key . '` = :setv' . $i;

                $prepare['setv' . $i] = $value;

                if ($max > $i) {
                    $sql .= ', ';
                }

                $i++;
            }
        }

        return array(
            'set'     => $sql,
            'prepare' => $prepare
        );
    }

    /**
     * The insert for SQLite
     *
     * @param array $params - the set params
     *
     * @return array
     */
    public static function createQuerySQLiteInsert($params)
    {
        $set_params = $params['set'];

        $max = count($set_params) - 1;
        $i   = 0;

        $prepare = array();
        $values  = array();

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

        return array(
            'insert'  => $sql,
            'prepare' => $prepare
        );
    }

    /**
     * Order Query Abschnitt
     *
     * @param array|string $params
     *
     * @return string
     */
    public static function createQueryOrder($params)
    {
        if (is_string($params)) {
            return ' ORDER BY ' . $params;
        }

        if (is_array($params) && !empty($params)) {
            $sql        = ' ORDER BY ';
            $sortFields = array();

            foreach ($params as $key => $sort) {
                if (is_string($key)) {
                    $sortFields[] = '`' . $key . '` ' . $sort;
                } else {
                    $sortFields[] = '`' . $sort . '`';
                }
            }

            return $sql . implode(',', $sortFields);
        }

        return '';
    }

    /**
     * Limit Query Abschnitt
     *
     * @param string|integer $params
     *
     * @return array
     */
    public static function createQueryLimit($params)
    {
        $sql     = ' LIMIT ';
        $prepare = array();

        if (strpos($params, ',') === false) {
            $limit1 = (int)trim($params);

            $prepare[':limit1'] = array($limit1, \PDO::PARAM_INT);

            $sql .= ':limit1';
        } else {
            $limit = explode(',', $params);

            if (!isset($limit[0]) || !isset($limit[1])) {
                return array(
                    'limit'   => '',
                    'prepare' => $prepare
                );
            }

            $limit1 = (int)trim($limit[0]);
            $limit2 = (int)trim($limit[1]);

            if ($limit1 < 0) {
                $limit1 = 0;
            }

            if ($limit2 < 0) {
                $limit2 = 0;
            }

            $prepare[':limit1'] = array($limit1, \PDO::PARAM_INT);
            $prepare[':limit2'] = array($limit2, \PDO::PARAM_INT);

            $sql .= ':limit1,:limit2';
        }

        return array(
            'limit'   => $sql,
            'prepare' => $prepare
        );
    }

    /**
     * Is order clause valid?
     *
     * @param string $value
     * @param array $allowed - allowed fields
     *
     * @return bool
     */
    public static function isOrderValid($value, $allowed = array())
    {
        if (!is_string($value)) {
            return false;
        }

        $value = trim($value);

        if (strpos($value, ' ') === false) {
            foreach ($allowed as $field) {
                if ($value === $field) {
                    return true;
                }
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
    public static function isWhereValid(array $where, $allowed = array())
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
