<?php

use \QUI\Database\DB as DB;

require_once 'DB_Test_Methods.php';

class DBSQLiteTest extends DB_Test_Methods
{
    public function getDBConection()
    {
        // pdo db
        return new DB(array(
            'driver' => 'sqlite',
            'dbname' => 'test.sqlite'
        ));
    }
}