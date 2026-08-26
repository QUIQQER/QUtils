<?php

namespace QUITest\QUI\Database;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use QUI\Database\DB;

require_once __DIR__ . '/DBHelper.php';

/**
 * Class DBSQLiteTest
 *
 * SQLite compatibility tests for the legacy database wrapper.
 */
class DBSQLiteTest extends DBHelper
{
    private Connection $Connection;
    private DB $Database;

    protected function setUp(): void
    {
        $this->Connection = DriverManager::getConnection([
            'driver' => 'pdo_sqlite',
            'memory' => true
        ]);
        $this->Connection->executeStatement(
            'CREATE TABLE legacy_sqlite_test (id INTEGER PRIMARY KEY AUTOINCREMENT, value TEXT NOT NULL)'
        );
        $this->Database = new DB(['doctrine' => $this->Connection]);
    }

    protected function tearDown(): void
    {
        $this->Connection->close();
    }

    public function testInjectedDoctrineConnectionIsRecognizedAsSqlite(): void
    {
        self::assertTrue($this->Database->isSQLite());
        self::assertSame('sqlite', $this->Database->getAttribute('driver'));
    }

    public function testLegacyInsertUsesSqliteSyntax(): void
    {
        $this->Database->insert('legacy_sqlite_test', ['value' => 'works']);

        self::assertSame([
            [
                'id' => 1,
                'value' => 'works'
            ]
        ], $this->Database->fetch([
            'from' => 'legacy_sqlite_test'
        ]));
    }

    public function testVersionUsesSqliteVersionFunction(): void
    {
        self::assertSame(
            $this->Connection->fetchOne('SELECT sqlite_version()'),
            $this->Database->getVersion()
        );
    }
}
