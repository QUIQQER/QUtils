<?php

namespace QUITest\QUI\Database;

/**
 * Class DB_Test_Methods
 * @todo test muss gescrieben, problem -> zugangsdaten
 *
 * @package QUITest\Database
 */
abstract class DBHelper extends \PHPUnit_Framework_TestCase
{
//    public function testDB()
//    {
//        $DataBase = $this->getDBConection();
//
//        $Table  = $DataBase->table();
//        $tables = $Table->getTables();
//
//        $table = 'test';
//
//        $Table->delete('unknown');
//
//        if ($Table->exist($table)) {
//            $Table->delete($table);
//        }
//
//        // create
//        $Table->create($table, array(
//            'id'  => 'int(10)',
//            'val' => 'text'
//        ));
//
//        // get field test
//        $fields = $Table->getFields($table);
//
//        if (!isset($fields[0]) || $fields[0] != 'id') {
//            $this->fail('something went wrong on getFields');
//        }
//
//
//        $Table->optimize($table);
//
//        // test data
//        $DataBase->insert($table, array(
//            'id'  => 1,
//            'val' => 'text'
//        ));
//
//        $DataBase->insert($table, array(
//            'id'  => 2,
//            'val' => 'text für id 2'
//        ));
//
//        $DataBase->insert($table, array(
//            'id'  => 3,
//            'val' => 'text für id 3'
//        ));
//
//        $DataBase->insert($table, array(
//            'id'  => 4,
//            'val' => 'text für id 4'
//        ));
//
//
//        $result = $DataBase->fetch(array(
//            'from'  => $table,
//            'where' => array(
//                'id' => 2
//            )
//        ));
//
//        if (!isset($result[0])) {
//            $this->fail('something went wrong on insert and fetching DB data');
//        }
//
//
//        // extend table
//        $Table->appendFields($table, array(
//            'third_field' => 'varchar(200)'
//        ));
//
//        $fields = $Table->getFields($table);
//
//        if ($DataBase->isSQLite()) {
//        }
//
//
//        if (!isset($fields[2]) || $fields[2] != 'third_field') {
//            $this->fail('something went wrong on appendFields');
//        }
//
//        // delete data
//        $DataBase->delete($table, array(
//            'id' => 2
//        ));
//
//        $result = $DataBase->fetch(array(
//            'select' => 'id',
//            'from'   => $table,
//            'where'  => array(
//                'id' => 2
//            )
//        ));
//
//        if (count($result)) {
//            $this->fail('DB delete entry not working');
//        }
//
//        // count with FETCH_STYLE error
//        $count = $DataBase->fetch(array(
//            'count' => 'count',
//            'from'  => $table
//        ), '____________________');
//
//        if (!isset($count[0]['count'])) {
//            $this->fail('Error on db fetch count');
//        }
//
//        $count = $DataBase->fetch(array(
//            'count' => array(
//                'select' => 'id',
//                'as'     => 'count'
//            ),
//            'from'  => $table
//        ));
//
//        if (!isset($count[0]['count'])) {
//            $this->fail('Error on db fetch count');
//        }
//
//        // update
//        $DataBase->update(
//            $table,
//            array('val' => 'other text'),
//            array('id' => 1)
//        );
//
//        $result = $DataBase->fetch(array(
//            'select' => 'val',
//            'from'   => $table,
//            'where'  => array(
//                'id' => 1
//            )
//        ));
//
//        if ($result[0]['val'] != 'other text') {
//            $this->fail('Error on db update');
//        }
//
//        // limit
//        $result = $DataBase->fetch(array(
//            'select' => array('id', 'val'),
//            'from'   => $table,
//            'limit'  => 1
//        ));
//
//        if (count($result) != 1) {
//            $this->fail('Error on db update limit');
//        }
//
//        if (!isset($result[0]['id'])
//            || !isset($result[0]['val'])
//        ) {
//            $this->fail('Error on db select. multi select via array not working');
//        }
//
//        // where
//        $result = $DataBase->fetch(array(
//            'from'  => $table,
//            'where' => array(
//                'val' => array(
//                    'value' => 'id ',
//                    'type'  => '%LIKE%'
//                )
//            )
//        ));
//
//        if (count($result) != 2) {
//            $this->fail('Error on db select. where %LIKE%');
//        }
//
//        $result = $DataBase->fetch(array(
//            'from'  => $table,
//            'where' => array(
//                'val' => array(
//                    'value' => 'id ',
//                    'type'  => '%LIKE'
//                )
//            )
//        ));
//
//        if (count($result) != 0) {
//            $this->fail('Error on db select. where %LIKE');
//        }
//
//        $result = $DataBase->fetch(array(
//            'from'  => $table,
//            'where' => array(
//                'val' => array(
//                    'value' => 'id ',
//                    'type'  => 'LIKE%'
//                )
//            )
//        ));
//
//        if (count($result) != 0) {
//            $this->fail('Error on db select. where LIKE%');
//        }
//
//
//        $Table->delete($table);
//
//        if ($Table->exist($table)) {
//            $this->fail('Drop Table failed');
//        }
//    }
//
//    public function testCreateTable()
//    {
//        try {
//            $DataBase = $this->getDBConection();
//            $Table    = $DataBase->Table();
//            $Table->create('test', '');
//
//            $this->fail('no exception on Table->create thrown.');
//        } catch (\QUI\Database\Exception $Exception) {
//        }
//    }
//
//    public function testPrimaryKey()
//    {
//        $DataBase = $this->getDBConection();
//        $Table    = $DataBase->Table();
//
//        // You can't modify SQLite tables in any significant way after they have been created
//        if ($DataBase->isSQLite()) {
//            return;
//        }
//
//        $table = 'test';
//
//        $Table->delete($table);
//        $Table->create($table, array(
//            'id'  => 'int(10)',
//            'txt' => 'text'
//        ));
//
//
//        if ($Table->issetPrimaryKey($table, 'id')) {
//            $this->fail('id in test table cant be a PrimaryKey');
//        }
//
//        $Table->setPrimaryKey($table, 'id');
//
//        if (!$Table->issetPrimaryKey($table, 'id')) {
//            $this->fail('id in test table is no PrimaryKey');
//        }
//
//        $Table->delete($table);
//    }
//
//    public function testIndex()
//    {
//        $DataBase = $this->getDBConection();
//        $Table    = $DataBase->Table();
//
//        $table = 'test';
//
//        $Table->delete($table);
//        $Table->create($table, array(
//            'id'  => 'int(10)',
//            'txt' => 'text'
//        ));
//
//
//        if ($Table->issetIndex($table, 'id')) {
//            $this->fail('id in test table cant be a index');
//        }
//
//        $Table->setIndex($table, 'id');
//
//        if (!$Table->issetIndex($table, 'id')) {
//            $this->fail('id in test table is no index');
//        }
//
//        $Table->delete($table);
//    }
//
//    public function testSetAutoIncrement()
//    {
//        $DataBase = $this->getDBConection();
//        $Table    = $DataBase->Table();
//
//        if ($DataBase->isSQLite()) {
//            return;
//        }
//
//        $table = 'test';
//
//        $Table->delete($table);
//        $Table->create($table, array(
//            'id'  => 'int(10)',
//            'txt' => 'text'
//        ));
//
//        $Table->setAutoIncrement($table, 'id');
//
//
//        $Table->delete($table);
//    }
//
//    public function testSetFulltext()
//    {
//        $DataBase = $this->getDBConection();
//        $Table    = $DataBase->Table();
//
//        if ($DataBase->isSQLite()) {
//            return;
//        }
//
//        $table = 'test';
//
//        $Table->delete($table);
//        $Table->create($table, array(
//            'id'  => 'int(10)',
//            'txt' => 'text'
//        ));
//
//        $Table->setFulltext($table, 'txt');
//
//        if (!$Table->issetFulltext($table, 'txt')) {
//            $this->fail('txt must be fulltext');
//        }
//
//
//        $Table->delete($table);
//    }
//
//    public function testGetTable()
//    {
//        $DataBase = $this->getDBConection();
//        $Table    = $DataBase->Table();
//
//        // create some tables
//        $Table->delete('test1');
//        $Table->create('test1', array(
//            'id'  => 'int(10)',
//            'txt' => 'text'
//        ));
//
//        $Table->delete('test2');
//        $Table->create('test2', array(
//            'id'  => 'int(10)',
//            'txt' => 'text'
//        ));
//
//        $Table->delete('test3');
//        $Table->create('test3', array(
//            'id'  => 'int(10)',
//            'txt' => 'text'
//        ));
//
//        $list = $Table->getTables();
//
//        if (!in_array('test1', $list)) {
//            $this->fail('test1 table is missing');
//        }
//
//        if (!in_array('test2', $list)) {
//            $this->fail('test2 table is missing');
//        }
//
//        if (!in_array('test3', $list)) {
//            $this->fail('test3 table is missing');
//        }
//
//        $Table->delete('test1');
//        $Table->delete('test2');
//        $Table->delete('test3');
//    }
//
//    public function testTruncate()
//    {
//        $DataBase = $this->getDBConection();
//        $Table    = $DataBase->Table();
//
//        $table = 'test1';
//
//        // create some tables
//        $Table->delete($table);
//        $Table->create($table, array(
//            'id'  => 'int(10)',
//            'val' => 'text'
//        ));
//
//        $DataBase->insert($table, array(
//            'id'  => 2,
//            'val' => 'text für id 2'
//        ));
//
//        $DataBase->insert($table, array(
//            'id'  => 3,
//            'val' => 'text für id 3'
//        ));
//
//        $DataBase->insert($table, array(
//            'id'  => 4,
//            'val' => 'text für id 4'
//        ));
//
//        $Table->truncate($table);
//
//        $result = $DataBase->fetch(array(
//            'from' => $table
//        ));
//
//        if (count($result)) {
//            $this->fail('Table->truncate not working');
//        }
//
//        $Table->delete($table);
//
//        $Table->truncate($table);
//    }
}
