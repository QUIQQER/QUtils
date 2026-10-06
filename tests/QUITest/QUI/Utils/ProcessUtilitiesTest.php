<?php

namespace QUITest\QUI\Utils;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use QUI\Exception;
use QUI\Utils\System;
use QUI\Utils\System\File;
use QUI\Utils\System\Webserver;
use QUI\Utils\Text\PDFToText;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;

class ProcessUtilitiesTest extends TestCase
{
    private string $directory;
    private string|false $originalPath;
    private array $originalEnvironment;
    private array $originalServer;

    protected function setUp(): void
    {
        $this->originalPath = getenv('PATH');
        $this->originalEnvironment = $_ENV;
        $this->originalServer = $_SERVER;
        $this->directory = sys_get_temp_dir() . '/quiqqer-process-' . bin2hex(random_bytes(8));
        mkdir($this->directory);
        mkdir($this->directory . '/bin');

        // Symfony also reads the superglobals, depending on PHP's variables_order.
        $path = $this->directory . '/bin';
        putenv('PATH=' . $path);
        $_ENV['PATH'] = $path;
        $_SERVER['PATH'] = $path;
    }

    protected function tearDown(): void
    {
        $_ENV = $this->originalEnvironment;
        $_SERVER = $this->originalServer;

        if ($this->originalPath === false) {
            putenv('PATH');
        } else {
            putenv('PATH=' . $this->originalPath);
        }

        File::deleteDir($this->directory);
    }

    public function testCommandDetectionDoesNotExecuteTheCommand(): void
    {
        $marker = $this->directory . '/executed';
        $body = 'file_put_contents(' . var_export($marker, true) . ', "executed");';
        $this->createExecutable('test-command', $body);

        $this->assertTrue(System::isSystemFunctionCallable('test-command'));
        $this->assertTrue(System::isSystemFunctionCallable('echo'));
        $this->assertFalse(System::isSystemFunctionCallable('missing-command'));
        $this->assertFalse(System::isSystemFunctionCallable('test-command; echo injected'));
        $this->assertFalse(System::isSystemFunctionCallable('$(echo injected)'));
        $this->assertFileDoesNotExist($marker);
    }

    public function testWebserverDetectionAndVersionUseTheAvailableBinary(): void
    {
        $this->createExecutable('httpd', <<<'PHP'
if ($argc !== 2 || $argv[1] !== '-v') {
    exit(1);
}

echo 'Server version: Apache/2.4.99 (Unix)';
PHP);
        $Detect = new \ReflectionMethod(Webserver::class, 'detectInstalledWebserverCLI');

        $this->assertSame(Webserver::WEBSERVER_APACHE, $Detect->invoke(null));
        $this->assertSame(['2', '4', '99'], Webserver::detectApacheVersion());

        unlink($this->directory . '/bin/httpd');
        $this->createExecutable('nginx', 'exit(0);');
        $this->assertSame(Webserver::WEBSERVER_NGINX, $Detect->invoke(null));

        unlink($this->directory . '/bin/nginx');
        $this->expectException(Exception::class);
        $Detect->invoke(null);
    }

    public function testCommandDetectionRequiresOnlyProcessExecution(): void
    {
        $cases = [
            'exec,shell_exec,system' => 'available',
            'proc_open' => 'unavailable'
        ];

        foreach ($cases as $disabledFunctions => $expected) {
            $Process = new Process([
                PHP_BINARY,
                '-d',
                'disable_functions=' . $disabledFunctions,
                $this->fixturePath('command.php'),
                $this->autoloadPath()
            ], timeout: 15);
            $Process->mustRun();

            $this->assertSame($expected, $Process->getOutput());
        }
    }

    public function testApacheFailureDoesNotReturnAVersionFromFailedOutput(): void
    {
        $this->createExecutable('apache2', 'echo "Apache/2.4.99"; exit(1);');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Could not detect Apache Version');
        Webserver::detectApacheVersion();
    }

    public function testPdfConversionPreservesSpecialCharactersAndCleansUp(): void
    {
        $this->createPdfConverter();
        $filename = $this->directory . '/-report with spaces;$(echo injected)\'".pdf';
        file_put_contents($filename, "%PDF-1.4\n%%EOF\n");

        $this->assertSame('converted text', PDFToText::convert($filename));

        $arguments = json_decode(file_get_contents($this->directory . '/arguments.json'), true);
        $this->assertSame($filename, $arguments[1]);
        $this->assertCount(3, $arguments);
        $this->assertFileDoesNotExist($arguments[2]);
    }

    public function testPdfFailureRejectsPartialOutputAndCleansUp(): void
    {
        $this->createPdfConverter(true);
        $filename = $this->directory . '/failed.pdf';
        file_put_contents($filename, "%PDF-1.4\n%%EOF\n");

        try {
            PDFToText::convert($filename);
            $this->fail('A failed conversion must not return partial text.');
        } catch (Exception $Exception) {
            $this->assertSame('Could not create text from PDF.', $Exception->getMessage());
            $this->assertSame(404, $Exception->getCode());
        }

        $arguments = json_decode(file_get_contents($this->directory . '/arguments.json'), true);
        $this->assertFileDoesNotExist($arguments[2]);
    }

    public function testMissingPdfConverterUsesTheExistingException(): void
    {
        $filename = $this->directory . '/input.pdf';
        file_put_contents($filename, "%PDF-1.4\n%%EOF\n");

        $this->expectException(Exception::class);
        $this->expectExceptionCode(500);
        $this->expectExceptionMessage('Could not use pdftotext.');
        PDFToText::convert($filename);
    }

    public function testPdfVersionTimeoutUsesTheExistingException(): void
    {
        $this->createExecutable('pdftotext', 'sleep(30);');
        $filename = $this->directory . '/input.pdf';
        file_put_contents($filename, "%PDF-1.4\n%%EOF\n");

        $this->expectException(Exception::class);
        $this->expectExceptionCode(500);
        $this->expectExceptionMessage('Could not use pdftotext.');
        PDFToText::convert($filename);
    }

    public function testFileCountHandlesSpecialPathsAndFailedFind(): void
    {
        $root = $this->directory . '/installation with spaces;$(echo injected)';
        mkdir($root);
        mkdir($root . '/empty');
        file_put_contents($root . '/first.txt', 'one');
        file_put_contents($root . "/line\nbreak.txt", 'two');

        $this->createExecutable('find', <<<'PHP'
if ($argc !== 5 || array_slice($argv, 2) !== ['-type', 'f', '-print0']) {
    exit(1);
}

$Files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(
    $argv[1],
    FilesystemIterator::SKIP_DOTS
));

foreach ($Files as $File) {
    echo $File->getPathname() . "\0";
}
PHP);
        $this->assertSame('2', $this->countFiles($root));
        $this->assertSame('0', $this->countFiles($root . '/empty'));

        // A partial result from a failed process must trigger the PHP fallback.
        $this->createExecutable('find', 'echo "partial\0"; exit(1);');
        $this->assertSame('2', $this->countFiles($root));

        unlink($this->directory . '/bin/find');
        $this->assertSame('2', $this->countFiles($root));
    }

    #[DataProvider('passwordModes')]
    public function testPasswordInputRestoresTerminalEcho(string $mode, bool $redirectOutput): void
    {
        if (!is_executable('/usr/bin/script') || !is_executable('/usr/bin/stty')) {
            $this->markTestSkipped('The terminal test requires script and stty.');
        }

        $autoload = var_export($this->autoloadPath(), true);
        $this->createExecutable('stty', 'require ' . $autoload . ";\n" . <<<'PHP'
$Process = Symfony\Component\Process\Process::fromShellCommandline(
    '/usr/bin/stty "${:MODE}" < /dev/tty',
    env: ['MODE' => $argv[1]],
    timeout: 10
);
$Process->mustRun();

if ($argv[1] === '-echo') {
    file_put_contents('/dev/tty', "ECHO_DISABLED\n");
}
PHP);
        $Child = new Process([
            PHP_BINARY,
            $this->fixturePath('console.php'),
            $this->autoloadPath(),
            $mode
        ]);
        $command = $Child->getCommandLine();
        $outputFile = $this->directory . '/console-output';

        if ($redirectOutput) {
            $command .= ' > ' . escapeshellarg($outputFile);
        }

        $Input = new InputStream();
        $Terminal = new Process(
            ['/usr/bin/script', '-q', '-e', '-c', $command, '/dev/null'],
            input: $Input,
            timeout: 15
        );
        $sent = false;
        $password = 'private password;$(echo secret)';

        try {
            $Terminal->mustRun(static function (
                string $type,
                string $output
            ) use (
                $Terminal,
                $Input,
                $password,
                &$sent
            ): void {
                if (!$sent && str_contains($Terminal->getOutput(), 'ECHO_DISABLED')) {
                    $Input->write($password . "\n");
                    $sent = true;
                }
            });
        } finally {
            $Input->close();
        }

        $output = $Terminal->getOutput();

        if ($redirectOutput) {
            $output .= file_get_contents($outputFile);
        }

        $this->assertStringContainsString('ECHO_RESTORED', $output);

        if ($mode === 'success') {
            $this->assertStringContainsString('PASSWORD_HASH=' . hash('sha256', $password), $output);
            $this->assertStringNotContainsString($password, $output);
        } else {
            $this->assertStringContainsString('READ_FAILED', $output);
        }
    }

    public static function passwordModes(): array
    {
        return [
            'successful read' => ['success', false],
            'redirected output' => ['success', true],
            'failed read' => ['failure', false]
        ];
    }

    private function createExecutable(string $name, string $body): void
    {
        $filename = $this->directory . '/bin/' . $name;
        file_put_contents($filename, '#!' . PHP_BINARY . "\n<?php\n" . $body . "\n");
        chmod($filename, 0700);
    }

    private function createPdfConverter(bool $fail = false): void
    {
        $log = var_export($this->directory . '/arguments.json', true);
        $body = <<<'PHP'
if ($argv[1] === '-v') {
    fwrite(STDERR, 'pdftotext version fixture');
    exit(0);
}

$arguments = json_encode($argv, JSON_THROW_ON_ERROR);
PHP;
        $body .= "\nfile_put_contents(" . $log . ', $arguments);';
        $body .= "\n" . 'file_put_contents($argv[2], "converted text");';

        if ($fail) {
            $body .= "\nexit(1);";
        }

        $this->createExecutable('pdftotext', $body);
    }

    private function countFiles(string $root): string
    {
        $Process = new Process([
            PHP_BINARY,
            $this->fixturePath('installation.php'),
            $this->autoloadPath(),
            $root
        ], timeout: 15);
        $Process->mustRun();

        return $Process->getOutput();
    }

    private function autoloadPath(): string
    {
        return dirname(__DIR__, 6) . '/autoload.php';
    }

    private function fixturePath(string $name): string
    {
        return dirname(__DIR__, 3) . '/Fixtures/ProcessUtilities/' . $name;
    }
}
