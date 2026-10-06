<?php

/**
 * This file contains Utils_Text_PDFToText
 */

namespace QUI\Utils\Text;

use QUI;
use Symfony\Component\Process\Exception\ExceptionInterface;
use Symfony\Component\Process\Process;

use function file_exists;
use function file_get_contents;
use function realpath;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

/**
 * Converts a pdf to text
 *
 *
 * @requires pdftotext (for ubuntu: sudo apt-get install poppler-utils)
 */
class PDFToText extends QUI\QDOM
{
    /**
     * Convert the pdf to text and return the text
     *
     * @param string $filename - path to PDF File
     *
     * @return string
     * @throws QUI\Exception
     */
    public static function convert(string $filename): string
    {
        if (!file_exists($filename)) {
            throw new QUI\Exception('File could not be read.', 404);
        }

        $data = QUI\Utils\System\File::getInfo($filename, [
            'mime_type' => true
        ]);

        if ($data['mime_type'] !== 'application/pdf') {
            throw new QUI\Exception('File is not a PDF.', 404);
        }

        try {
            $Version = new Process(['pdftotext', '-v'], timeout: 10);
            $Version->mustRun();
        } catch (ExceptionInterface) {
            throw new QUI\Exception('Could not use pdftotext.', 500);
        }

        $output = $Version->getOutput() . $Version->getErrorOutput();

        if (!str_contains($output, 'pdftotext version')) {
            throw new QUI\Exception('Could not use pdftotext.', 500);
        }

        // An absolute path also prevents filenames starting with '-' becoming options.
        $inputFile = realpath($filename);

        if ($inputFile === false) {
            throw new QUI\Exception('File could not be read.', 404);
        }

        $temporaryFile = tempnam(sys_get_temp_dir(), 'quiqqer-pdftotext-');

        if ($temporaryFile === false) {
            throw new QUI\Exception('Could not create text from PDF.', 404);
        }

        try {
            $Process = new Process(['pdftotext', $inputFile, $temporaryFile], timeout: 300);
            $Process->disableOutput();
            $Process->mustRun();

            if (!file_exists($temporaryFile)) {
                throw new QUI\Exception('Could not create text from PDF.', 404);
            }

            $content = file_get_contents($temporaryFile);

            return $content === false ? '' : $content;
        } catch (ExceptionInterface) {
            throw new QUI\Exception('Could not create text from PDF.', 404);
        } finally {
            if (file_exists($temporaryFile)) {
                unlink($temporaryFile);
            }
        }
    }
}
