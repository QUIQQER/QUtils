<?php

use \QUI\Database\DB as DB;

class DBTest extends PHPUnit_Framework_TestCase
{
    public function testDB()
    {
        $DataBase = new DB(array(
            'driver'   => $GLOBALS['DB_DRIVER'],
            'host'     => $GLOBALS['DB_HOST'],
            'user'     => $GLOBALS['DB_USER'],
            'password' => $GLOBALS['DB_PASSWD'],
            'dbname'   => $GLOBALS['DB_DBNAME']
        ));

        $Table  = $DataBase->Table();
        $tables = $Table->getTables();

        $table = 'test';

        $Table->delete( 'unknown' );

        if ( $Table->exist( $table ) ) {
            $Table->delete( $table );
        }

        // create
        $Table->create($table, array(
            'id'  => 'int(10)',
            'val' => 'text'
        ));

        // get field test
        $fields = $Table->getFields( $table );

        if ( !isset( $fields[0] ) || $fields[0] != 'id' ) {
            $this->fail( 'something went wrong on getFields' );
        }


        $Table->optimize( $table );

        // test data
        $DataBase->insert($table, array(
            'id'  => 1,
            'val' => 'text'
        ));

        $DataBase->insert($table, array(
            'id'  => 2,
            'val' => 'text für id 2'
        ));

        $DataBase->insert($table, array(
            'id'  => 3,
            'val' => 'text für id 3'
        ));

        $DataBase->insert($table, array(
            'id'  => 4,
            'val' => 'text für id 4'
        ));


        $result = $DataBase->fetch(array(
            'from' => $table,
            'where' => array(
                'id' => 2
            )
        ));

        if ( !isset( $result[0] ) ) {
            $this->fail( 'something went wrong on insert and fetching DB data' );
        }


        // extend table
        $Table->appendFields($table, array(
            'third_field' => 'varchar(200)'
        ));

        $fields = $Table->getFields( $table );

        if ( !isset( $fields[2] ) || $fields[2] != 'third_field' ) {
            $this->fail( 'something went wrong on appendFields' );
        }

        // delete data
        $DataBase->delete($table, array(
            'id'  => 2
        ));

        $result = $DataBase->fetch(array(
            'select' => 'id',
            'from'   => $table,
            'where'  => array(
                'id' => 2
            )
        ));

        if ( count( $result ) ) {
            $this->fail( 'DB delete entry not working' );
        }

        // count with FETCH_STYLE error
        $count = $DataBase->fetch(array(
            'count' =>  'count',
            'from'   => $table
        ), '____________________');

        if ( !isset( $count[0]['count'] ) ) {
            $this->fail( 'Error on db fetch count' );
        }

        $count = $DataBase->fetch(array(
            'count' =>  array(
                'select' => 'id',
                'as'     => 'count'
            ),
            'from'   => $table
        ));

        if ( !isset( $count[0]['count'] ) ) {
            $this->fail( 'Error on db fetch count' );
        }

        // update
        $DataBase->update(
            $table,
            array( 'val' => 'other text' ),
            array( 'id' => 1 )
        );

        $result = $DataBase->fetch(array(
            'select' => 'val',
            'from'   => $table,
            'where'  => array(
                'id' => 1
            )
        ));

        if ( $result[0]['val'] != 'other text' ) {
            $this->fail( 'Error on db update' );
        }

        // limit
        $result = $DataBase->fetch(array(
            'select' => array('id', 'val'),
            'from'  => $table,
            'limit' => 1
        ));

        if ( count( $result ) != 1 ) {
            $this->fail( 'Error on db update limit' );
        }

        if ( !isset( $result[0]['id'] ) ||
             !isset( $result[0]['val'] ) )
        {
            $this->fail( 'Error on db select. multi select via array not working' );
        }

        // where
        $result = $DataBase->fetch(array(
            'from'  => $table,
            'where' => array(
                'val' =>  array(
                    'value' => 'id ',
                    'type' => '%LIKE%'
                )
            )
        ));

        if ( count( $result ) != 2 ) {
            $this->fail( 'Error on db select. where %LIKE%' );
        }

        $result = $DataBase->fetch(array(
            'from'  => $table,
            'where' => array(
                'val' =>  array(
                    'value' => 'id ',
                    'type' => '%LIKE'
                )
            )
        ));

        if ( count( $result ) != 0 ) {
            $this->fail( 'Error on db select. where %LIKE' );
        }

        $result = $DataBase->fetch(array(
            'from'  => $table,
            'where' => array(
                'val' =>  array(
                    'value' => 'id ',
                    'type' => 'LIKE%'
                )
            )
        ));

        if ( count( $result ) != 0 ) {
            $this->fail( 'Error on db select. where LIKE%' );
        }
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
}