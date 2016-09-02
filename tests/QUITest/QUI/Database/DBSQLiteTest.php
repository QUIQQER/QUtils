<?php

namespace QUITest\QUI\Database;

use \QUI\Database\DB as DB;

/**
 * Class DBSQLiteTest
 * @package QUITest\Database
 */
class DBSQLiteTest extends DBTest
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
