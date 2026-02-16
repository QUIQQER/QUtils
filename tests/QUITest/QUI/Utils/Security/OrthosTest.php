<?php

namespace QUITest\QUI\Utils\Security;

use QUI\Utils\Security\Orthos as Orthos;

/**
 * Class OrthosTest
 */
class OrthosTest extends \PHPUnit\Framework\TestCase
{
    public function testClear()
    {
        $result = Orthos::clear('a test string \' %%% **');

        $this->assertFalse($result != 'a test string  %%% **', '\QUI\Utils\Security\Orthos::clear fail');
    }

    public function testClearArray()
    {
        $result = Orthos::clearArray([
            'a test string \' %%% **',
            'support@pcsg.de',
        ]);

        $this->assertFalse($result[0] != 'a test string  %%% **', '\QUI\Utils\Security\Orthos::testClearArray fail');

        $this->assertFalse($result[1] != 'support@pcsg.de', '\QUI\Utils\Security\Orthos::testClearArray fail');

        $result = Orthos::clearArray('no_array');

        $this->assertTrue(is_array($result), '\QUI\Utils\Security\Orthos::testClearArray return no array');

        // multi array test
        $result = Orthos::clearArray([
            'a test string \' %%% **',
            [
                'a test string \' %%% **',
                'support@pcsg.de'
            ]
        ]);

        $this->assertFalse(
            $result[1][0] != 'a test string  %%% **',
            '\QUI\Utils\Security\Orthos::testClearArray fail @ multi array test'
        );
    }

    public function testClearFormRequest()
    {
        $_TEST_REQUEST = [
            'name' => 'val',
            'test' => '<script>alert(1)</script>'
        ];

        $result = Orthos::clearFormRequest($_TEST_REQUEST);

        $this->assertFalse(
            strpos($result['test'], '<') !== false,
            '\QUI\Utils\Security\Orthos::clearFormRequest found < in the request'
        );
    }

    public function testClearMySQL()
    {
        $result = Orthos::clearMySQL('"; SELECT FROM');
        $this->assertIsString($result);
    }

    public function testCleanHTML()
    {
        $result = Orthos::cleanHTML('<b>test</b>');

        $this->assertFalse($result != 'test', '\QUI\Utils\Security\Orthos::testCleanHTML <b>test</b> wrong parsed');

        $result = Orthos::cleanHTML('<some_unknown_tag><p><b>test</b></p></some_unknown_tag>');

        $this->assertFalse(
            $result != 'test',
            '\QUI\Utils\Security\Orthos::testCleanHTML
                <some_unknown_tag><p><b>test</b></p></some_unknown_tag> wrong parsed'
        );
    }

    public function testDate()
    {
        $this->assertSame(0, Orthos::date(35), '\QUI\Utils\Security\Orthos::date is incorrect. 35 is a day');
        $this->assertSame(12, Orthos::date('12', 'DAY'), '\QUI\Utils\Security\Orthos::date is incorrect. 12 is no day');
        $this->assertSame(0, Orthos::date(35, 'MONTH'), '\QUI\Utils\Security\Orthos::date is incorrect. 35 is a month');
        $this->assertSame(
            12,
            Orthos::date('12', 'MONTH'),
            '\QUI\Utils\Security\Orthos::date is incorrect. 12 is no month'
        );
        $this->assertSame(35, Orthos::date(35, 'YEAR'), '\QUI\Utils\Security\Orthos::date is incorrect. 35 is no year');
        $this->assertSame(
            35,
            Orthos::date('35', 'YEAR'),
            '\QUI\Utils\Security\Orthos::date is incorrect. 35 is no year'
        );
        $this->assertSame(
            0,
            Orthos::date('__35', 'YEAR'),
            '\QUI\Utils\Security\Orthos::date is incorrect. __35 is no year'
        );
    }

    public function testCheckdate()
    {
        $this->assertFalse(
            Orthos::checkdate(10, 10, 0),
            '\QUI\Utils\Security\Orthos::checkdate is incorrect. 10, 10, 0 is a date'
        );

        $this->assertFalse(
            Orthos::checkdate('test', 10, 0),
            '\QUI\Utils\Security\Orthos::checkdate is incorrect. test, 10, 0 is a date'
        );

        $this->assertFalse(
            Orthos::checkdate(10, 'test', 'test'),
            '\QUI\Utils\Security\Orthos::checkdate is incorrect. 10, test, test is a date'
        );

        $this->assertFalse(
            Orthos::checkdate(30, 10, null),
            '\QUI\Utils\Security\Orthos::checkdate is incorrect. test, 10, null is a date'
        );

        $this->assertFalse(
            Orthos::checkdate(300, 1, 1990),
            '\QUI\Utils\Security\Orthos::checkdate is incorrect. 300, 1, 1990 is a date'
        );
    }

    public function testRemoveLineBreaks()
    {
        $str = "
            <p>test</p>
            <p>test</p>
            <p>test</p>
        ";

        $result = Orthos::removeLineBreaks($str);

        $this->assertFalse(
            strpos($result, "\n") !== false,
            '\QUI\Utils\Security\Orthos::removeLineBreaks removes no linebreaks'
        );
    }

    public function testCheckMailSyntax()
    {
        $this->assertTrue(
            Orthos::checkMailSyntax('support@pcsg.de'),
            '\QUI\Utils\Security\Orthos::checkMailSyntax is incorrect. 
                support@pcsg.de is no correct email'
        );

        $this->assertTrue(
            Orthos::checkMailSyntax('support.test@pcsg.de'),
            '\QUI\Utils\Security\Orthos::checkMailSyntax is incorrect.
                support.test@pcsg.de is no correct email'
        );
    }

    public function testCheckMySqlDatetimeSyntax()
    {
        $this->assertTrue(
            Orthos::checkMySqlDatetimeSyntax('2001-10-25 10:12:22'),
            '\QUI\Utils\Security\Orthos::checkMySqlDatetimeSyntax: 2001-10-25 10:12:22 is no mysql date'
        );

        $this->assertFalse(
            Orthos::checkMySqlDatetimeSyntax('2001-10-25'),
            '\QUI\Utils\Security\Orthos::checkMySqlDatetimeSyntax: 2001-10-25 mysql DatetimeSyntax but H:i:s failed'
        );

        $this->assertFalse(
            Orthos::checkMySqlDatetimeSyntax('test'),
            '\QUI\Utils\Security\Orthos::checkMySqlDatetimeSyntax: test is a mysql date'
        );
    }

    public function testGetPassword()
    {
        $pw1 = Orthos::getPassword(20);
        $pw2 = Orthos::getPassword(20);

        $this->assertFalse(mb_strlen($pw1) != 20, '\QUI\Utils\Security\Orthos::getPassword(20) return no 20 letters');

        $this->assertFalse($pw1 == $pw2, '\QUI\Utils\Security\Orthos::getPassword(20) return the same passwords');
    }


    public function testClearPath()
    {
        $path = '/var/www/vhost/domain/../lala/';
        $clear = Orthos::clearPath($path);

        $this->assertFalse($clear != '/var/www/vhost/domain/lala/', '\QUI\Utils\Security\Orthos::clearPath error');
    }

    public function testClearShell()
    {
        $clear = Orthos::clearShell('ls -l; ls -l');

        $this->assertFalse($clear != 'ls -l\; ls -l', '\QUI\Utils\Security\Orthos::clearShell error');
    }

    public function testParseInt()
    {
        $this->assertTrue(
            is_int(Orthos::parseInt('123')),
            '\QUI\Utils\Security\Orthos::parseInt error -> 123 is no int'
        );

        $this->assertTrue(
            is_int(Orthos::parseInt('hallo')),
            '\QUI\Utils\Security\Orthos::parseInt error -> hallo is not parsed to an int'
        );
    }

    public function testIsSpamMail()
    {
        $this->assertFalse(
            Orthos::isSpamMail('test@spaminator.de') === false,
            'test@spaminator.de is not marked as a spammail'
        );

        $this->assertFalse(Orthos::isSpamMail('test@pcsg.de'), 'test@pcsg.de is marked as a spammail');

        $this->assertFalse(Orthos::isSpamMail('test'), 'test is not marked as a spammail');
    }
}
