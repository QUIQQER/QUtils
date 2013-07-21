<?php

use \QUI\Database\DB as DB;

require_once 'DB_Test_Methods.php';

class DBMySQLTest extends DB_Test_Methods
{
    public function getDBConection()
    {
        return new DB(array(
            'driver'   => $GLOBALS['DB_DRIVER'],
            'host'     => $GLOBALS['DB_HOST'],
            'user'     => $GLOBALS['DB_USER'],
            'password' => $GLOBALS['DB_PASSWD'],
            'dbname'   => $GLOBALS['DB_DBNAME']
        ));
    }

    public function testDBException()
    {
        try
        {
            $DataBase = new DB(array(
                'driver'   => $GLOBALS['DB_DRIVER'],
                'host'     => $GLOBALS['DB_HOST'],
                'user'     => '__unknown',
                'password' => '',
                'dbname'   => $GLOBALS['DB_DBNAME']
            ));

            $this->fail( 'no exception thrown by bad DB data' );

        } catch ( \QUI\Database\Exception $Exception )
        {

        }
    }

    public function testSetFulltext()
    {
        // no fulltext search in sqlite
        // Create a Full-text Search (FTS) Table
        // CREATE VIRTUAL TABLE book USING fts4 (id, author, title, excerpt);
    }

    public function testSetAutoIncrement()
    {

    }
}