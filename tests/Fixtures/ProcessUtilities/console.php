<?php

use QUI\Utils\System\Console;
use Symfony\Component\Process\Process;

require $argv[1];

if ($argv[2] === 'failure') {
    fclose(STDIN);
}

try {
    $password = Console::readPassword();
    echo 'PASSWORD_HASH=' . hash('sha256', $password) . PHP_EOL;
} catch (TypeError) {
    echo 'READ_FAILED' . PHP_EOL;
}

$State = Process::fromShellCommandline('/usr/bin/stty -a < /dev/tty', timeout: 10);
$State->mustRun();

if (preg_match('/(?:^|\s)echo(?:\s|;|$)/', $State->getOutput())) {
    echo 'ECHO_RESTORED' . PHP_EOL;
}
