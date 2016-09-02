<?php

use QUI\Utils\Security\Orthos as Orthos;

class OrthosTest extends PHPUnit_Framework_TestCase
{
    public function testClear()
    {
        $result = Orthos::clear('a test string \' %%% **');

        if ($result != 'a test string  %%% **') {
            $this->fail('\QUI\Utils\Security\Orthos::clear fail');
        }
    }

    public function testClearArray()
    {
        $result = Orthos::clearArray(array(
            'a test string \' %%% **',
            'support@pcsg.de',
        ));

        if ($result[0] != 'a test string  %%% **') {
            $this->fail('\QUI\Utils\Security\Orthos::testClearArray fail');
        }

        if ($result[1] != 'support@pcsg.de') {
            $this->fail('\QUI\Utils\Security\Orthos::testClearArray fail');
        }

        $result = Orthos::clearArray('no_array');

        if (!is_array($result)) {
            $this->fail('\QUI\Utils\Security\Orthos::testClearArray return no array');
        }

        // multi array test
        $result = Orthos::clearArray(array(
            'a test string \' %%% **',
            array(
                'a test string \' %%% **',
                'support@pcsg.de'
            )
        ));

        if ($result[1][0] != 'a test string  %%% **') {
            $this->fail('\QUI\Utils\Security\Orthos::testClearArray fail @ multi array test');
        }
    }

    public function testClearFormRequest()
    {
        $_TEST_REQUEST = array(
            'name' => 'val',
            'test' => '<script>alert(1)</script>'
        );

        $result = Orthos::clearFormRequest($_TEST_REQUEST);

        if (strpos($result['test'], '<') !== false) {
            $this->fail('\QUI\Utils\Security\Orthos::clearFormRequest found < in the request');
        }
    }

    public function testClearMySQL()
    {
        $result = Orthos::clearMySQL('"; SELECT FROM');

    }

    public function testCleanHTML()
    {
        $result = Orthos::cleanHTML('<b>test</b>');

        if ($result != '<b>test</b>') {
            $this->fail('\QUI\Utils\Security\Orthos::testCleanHTML <b>test</b> wrong parsed');
        }

        $result
            = Orthos::cleanHTML('<some_unknown_tag><p><b>test</b></p></some_unknown_tag>');

        if ($result != '<p><b>test</b></p>') {
            $this->fail(
                '\QUI\Utils\Security\Orthos::testCleanHTML
                <some_unknown_tag><p><b>test</b></p></some_unknown_tag> wrong parsed'
            );
        }
    }

    public function testDate()
    {
        if (Orthos::date(35)) {
            $this->fail('\QUI\Utils\Security\Orthos::date is incorrect. 35 is a day');
        }

        if (!Orthos::date('12', 'DAY')) {
            $this->fail('\QUI\Utils\Security\Orthos::date is incorrect. 12 is no day');
        }

        if (Orthos::date(35, 'MONTH')) {
            $this->fail('\QUI\Utils\Security\Orthos::date is incorrect. 35 is a month');
        }

        if (!Orthos::date('12', 'MONTH')) {
            $this->fail('\QUI\Utils\Security\Orthos::date is incorrect. 12 is no month');
        }

        if (!Orthos::date(35, 'YEAR')) {
            $this->fail('\QUI\Utils\Security\Orthos::date is incorrect. 35 is no year');
        }

        if (!Orthos::date('35', 'YEAR')) {
            $this->fail('\QUI\Utils\Security\Orthos::date is incorrect. 35 is no year');
        }

        if (Orthos::date('__35', 'YEAR')) {
            $this->fail('\QUI\Utils\Security\Orthos::date is incorrect. __35 is no year');
        }
    }

    public function testCheckdate()
    {
        if (Orthos::checkdate(10, 10, 0)) {
            $this->fail('\QUI\Utils\Security\Orthos::checkdate is incorrect. 10, 10, 0 is a date');
        }

        if (Orthos::checkdate('test', 10, 0)) {
            $this->fail('\QUI\Utils\Security\Orthos::checkdate is incorrect. test, 10, 0 is a date');
        }

        if (Orthos::checkdate(10, 'test', 'test')) {
            $this->fail('\QUI\Utils\Security\Orthos::checkdate is incorrect. 10, test, test is a date');
        }

        if (Orthos::checkdate(30, 10, null)) {
            $this->fail('\QUI\Utils\Security\Orthos::checkdate is incorrect. test, 10, null is a date');
        }

        if (Orthos::checkdate(300, 1, 1990)) {
            $this->fail('\QUI\Utils\Security\Orthos::checkdate is incorrect. 300, 1, 1990 is a date');
        }
    }

    public function testRemoveLineBreaks()
    {
        $str
            = "
            <p>test</p>
            <p>test</p>
            <p>test</p>
        ";

        $result = Orthos::removeLineBreaks($str);

        if (strpos($result, "\n") !== false) {
            $this->fail('\QUI\Utils\Security\Orthos::removeLineBreaks removes no linebreaks');
        }
    }

    public function testCheckMailSyntax()
    {
        if (!Orthos::checkMailSyntax('support@pcsg.de')) {
            $this->fail('\QUI\Utils\Security\Orthos::checkMailSyntax is incorrect. support@pcsg.de is no correct email');
        }

        if (!Orthos::checkMailSyntax('support.test@pcsg.de')) {
            $this->fail(
                '\QUI\Utils\Security\Orthos::checkMailSyntax is incorrect.
                support.test@pcsg.de is no correct email'
            );
        }
    }

    public function testCheckMySqlDatetimeSyntax()
    {
        if (!Orthos::checkMySqlDatetimeSyntax('2001-10-25 10:12:22')) {
            $this->fail(
                '\QUI\Utils\Security\Orthos::checkMySqlDatetimeSyntax: 2001-10-25 10:12:22 is no mysql date'
            );
        }

        if (Orthos::checkMySqlDatetimeSyntax('2001-10-25')) {
            $this->fail(
                '\QUI\Utils\Security\Orthos::checkMySqlDatetimeSyntax: 2001-10-25 mysql DatetimeSyntax but H:i:s failed'
            );
        }

        if (Orthos::checkMySqlDatetimeSyntax('test')) {
            $this->fail(
                '\QUI\Utils\Security\Orthos::checkMySqlDatetimeSyntax: test is a mysql date'
            );
        }
    }

    public function testGetPassword()
    {
        $pw1 = Orthos::getPassword(20);
        $pw2 = Orthos::getPassword(20);

        if (mb_strlen($pw1) != 20) {
            $this->fail('\QUI\Utils\Security\Orthos::getPassword(20) return no 20 letters');
        }

        if ($pw1 == $pw2) {
            $this->fail('\QUI\Utils\Security\Orthos::getPassword(20) return the same passwords');
        }
    }


    public function testClearPath()
    {
        $path = '/var/www/vhost/domain/../lala/';
        $clear = \QUI\Utils\Security\Orthos::clearPath($path);

        if ($clear != '/var/www/vhost/domain/lala/') {
            $this->fail('\QUI\Utils\Security\Orthos::clearPath error');
        }
    }

    public function testClearShell()
    {
        $clear = \QUI\Utils\Security\Orthos::clearShell('ls -l; ls -l');

        if ($clear != 'ls -l\; ls -l') {
            $this->fail('\QUI\Utils\Security\Orthos::clearShell error');
        }
    }

    public function testParseInt()
    {
        if (!is_int(\QUI\Utils\Security\Orthos::parseInt('123'))) {
            $this->fail('\QUI\Utils\Security\Orthos::parseInt error -> 123 is no int');
        }

        if (!is_int(\QUI\Utils\Security\Orthos::parseInt('hallo'))) {
            $this->fail('\QUI\Utils\Security\Orthos::parseInt error -> hallo is not parsed to an int');
        }
    }

    public function testIsSpamMail()
    {
        if (\QUI\Utils\Security\Orthos::isSpamMail('test@spaminator.de')
            === false
        ) {
            $this->fail('test@spaminator.de is not marked as a spammail');
        }

        if (\QUI\Utils\Security\Orthos::isSpamMail('test@pcsg.de')) {
            $this->fail('test@pcsg.de is marked as a spammail');
        }

        if (\QUI\Utils\Security\Orthos::isSpamMail('test')) {
            $this->fail('test is not marked as a spammail');
        }
    }
}
