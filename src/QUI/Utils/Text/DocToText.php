<?php

/**
 * This file contains the Utils_Text_DocToText
 */

namespace QUI\Utils\Text;

use DOMDocument;
use QUI;
use ZipArchive;

use function file_exists;
use function fopen;
use function fread;
use function nl2br;
use function preg_replace;
use function trim;
use function utf8_encode;

use const LIBXML_NOENT;
use const LIBXML_NOERROR;
use const LIBXML_NOWARNING;
use const LIBXML_XINCLUDE;

/**
 * Extract content from various file formats to text
 *
 * @uses     ZipArchive
 * @requires ZipArchive
 *
 * @author   www.pcsg.de (Henning Leutz)
 */
class DocToText
{
    /**
     * Returns the content from a odx / docx file
     *
     * @param string $file - path to file
     *
     * @return string
     * @throws QUI\Exception
     */
    public static function convert(string $file): string
    {
        if (!file_exists($file)) {
            throw new QUI\Exception('File could not be read.', 404);
        }

        $Zip = new ZipArchive();

        if ($Zip->open($file) === false) {
            throw new QUI\Exception('File could not be read.', 404);
        }

        $data = QUI\Utils\System\File::getInfo($file, [
            'mime_type' => true
        ]);

        // doc
        switch ($data['mime_type']) {
            case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document':
                $ln = "word/document.xml";
                break;

            // odt
            case 'application/zip':
            case 'application/x-vnd.oasis.opendocument.text':
            case 'application/vnd.oasis.opendocument.text':
                $ln = "content.xml";
                break;

            default:
                throw new QUI\Exception('Unbekanntes Format.');
        }

        if (($index = $Zip->locateName($ln)) !== false) {
            $str = $Zip->getFromIndex($index);
            $Doc = new DOMDocument();
            $Doc->loadXML(
                $str,
                LIBXML_NOENT | LIBXML_XINCLUDE | LIBXML_NOERROR
                | LIBXML_NOWARNING
            );

            // $text = strip_tags($Doc->saveXML());
            $text = preg_replace('#<[^>]+>#', ' ', $Doc->saveXML());
            $text = preg_replace('/([ ]){2,}/', "$1", $text);
            $text = trim($text);

            $Zip->close();

            return $text;
        }

        return '';
    }

    /**
     * Convert a microsoft .doc file to text
     * from: http://blog.folkeraxmann.de/?p=318
     *
     * @param string $filename - Path to filename
     *
     * @return string
     * @throws QUI\Exception
     */
    public static function convertDoc(string $filename): string
    {
        if (!file_exists($filename)) {
            throw new QUI\Exception('File could not be read.', 404);
        }

        if (!($fh = fopen($filename, 'r'))) {
            throw new QUI\Exception('File could not be read.', 404);
        }

        $headers = fread($fh, 0xA00);

        # 1 = (ord(n)*1) ; Document has from 0 to 255 characters
        $n1 = (ord($headers[0x21C]) - 1);

        # 1 = ((ord(n)-8)*256) ; Document has from 256 to 63743 characters
        $n2 = ((ord($headers[0x21D]) - 8) * 256);

        # 1 = ((ord(n)*256)*256) ; Document has from 63744 to 16775423 characters
        $n3 = ((ord($headers[0x21E]) * 256) * 256);

        # (((ord(n)*256)*256)*256) ; Document has from 16775424 to 4294965504 characters
        $n4 = (((ord($headers[0x21F]) * 256) * 256) * 256);

        # Total length of text in the document
        $textLength          = ($n1 + $n2 + $n3 + $n4);
        $extracted_plaintext = fread($fh, $textLength);

        return utf8_encode(nl2br($extracted_plaintext));
    }
}
