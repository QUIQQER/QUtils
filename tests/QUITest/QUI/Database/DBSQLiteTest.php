<?php

namespace QUITest\QUI\Database;

require_once __DIR__ . '/DBHelper.php';

/**
 * Class DBSQLiteTest
 *
 * @todo test muss geschrieben werden
 */


class DBSQLiteTest extends DBHelper
{
    public function testPlaceholder(): void
    {
        $this->markTestSkipped('Legacy DBSQLite tests are not implemented yet.');
    }

//    public function getDBConection()
//    {
//        // pdo db
//        return new DB(array(
//            'driver' => 'sqlite',
//            'dbname' => 'test.sqlite'
//        ));
//    }
}
