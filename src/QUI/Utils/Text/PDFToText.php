<?php

/**
 * This file contains Utils_Text_PDFToText
 */

namespace QUI\Utils\Text;

use QUI;
use QUI\Utils\Security\Orthos;

/**
 * Converts a pdf to text
 *
 * @author   www.pcsg.de (Henning Leutz)
 * @package  com.pcsg.qui.utils.text
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
    public static function convert($filename)
    {
        if (!file_exists($filename)) {
            throw new QUI\Exception('File could not be read.', 404);
        }

        $data = QUI\Utils\System\File::getInfo($filename, array(
            'mime_type' => true
        ));

        if ($data['mime_type'] !== 'application/pdf') {
            throw new QUI\Exception('File is not a PDF.', 404);
        }


        $output = shell_exec('pdftotext 2>&1');

        if (strpos($output, 'pdftotext version') === false) {
            throw new QUI\Exception('Could not use pdftotext.', 500);
        }

        $tmp_file
              = '/tmp/' . str_replace(array('.', ' '), '', microtime()) . '.txt';
        $exec = 'pdftotext ' . $filename . ' ' . $tmp_file;

        system(Orthos::clearShell($exec));

        if (!file_exists($tmp_file)) {
            throw new QUI\Exception('Could not create text from PDF.', 404);
        }

        $content = file_get_contents($tmp_file);

        unlink($tmp_file);

        return $content;
    }
}
