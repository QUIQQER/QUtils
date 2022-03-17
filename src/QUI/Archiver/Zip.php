<?php

/**
 * This file contains \QUI\Archiver\Zip
 */

namespace QUI\Archiver;

use QUI;
use QUI\Exception;
use ZipArchive;

use function basename;
use function class_exists;
use function count;
use function file_exists;
use function is_array;
use function substr;

/**
 * ZIP archiver
 * zip and unzip files
 *
 * @copyright www.pcsg.de (Henning Leutz)
 * @uses      ZipArchive
 */
class Zip
{
    /**
     * constructor
     * Checks if ZipArchive exists as a php module
     * @throws Exception
     */
    public function __construct()
    {
        self::check();
    }

    /**
     * Check, if ZipArchive is enabled
     *
     * @return boolean
     * @throws Exception
     */
    public static function check(): bool
    {
        if (!class_exists('ZipArchive')) {
            throw new Exception(
                'Class ZipArchive not exist',
                404
            );
        }

        return true;
    }

    /**
     * From a folder created a ZIP Archive
     *
     * @param string $folder - Folder which is to be packed
     * @param string $zipFile - Name of new Zipfiles
     * @param array $ignore - Folder to be ignored
     *
     * @throws Exception
     */
    public static function zip(string $folder, string $zipFile, array $ignore = [])
    {
        self::check();

        $Zip = new ZipArchive();

        if ($Zip->open($zipFile, ZIPARCHIVE::CREATE) !== true) {
            throw new Exception('cannot open ' . $zipFile);
        }

        if (!is_array($ignore)) {
            $ignore = [];
        }

        if (substr($folder, -1) != '/') {
            $folder .= '/';
        }

        $File  = new QUI\Utils\System\File();
        $files = $File->readDirRecursiv($folder);

        foreach ($files as $_folder => $_file) {
            if (!empty($ignore) && in_array($_folder, $ignore)) {
                continue;
            }

            $oldFolder = $folder . $_folder;

            for ($i = 0, $len = count($_file); $i < $len; $i++) {
                if (file_exists($oldFolder . $_file[$i])) {
                    $Zip->addFile(
                        $oldFolder . $_file[$i],
                        $_folder . $_file[$i]
                    );
                }
            }
        }

        $Zip->close();
    }


    /**
     * Puts the given files in a zip file
     *
     * @param string[] $files - Paths of the files to zip
     * @param string $zipFile - Path to the zip file (folders have to already exist)
     *
     * @throws Exception
     */
    public static function zipFiles(array $files, string $zipFile)
    {
        self::check();

        $Zip = new ZipArchive();

        if ($Zip->open($zipFile, ZIPARCHIVE::CREATE) !== true) {
            throw new Exception('cannot open ' . $zipFile);
        }

        if (count($files) == 0) {
            throw new Exception("You need to specify at least one file to zip in an array");
        }

        foreach ($files as $file) {
            if (file_exists($file)) {
                $Zip->addFile($file, basename($file));
            }
        }

        $Zip->close();
    }


    /**
     * Unzip the file
     *
     * @param string $zipFile - path to zip file
     * @param string $to - path to the destination folder
     *
     * @throws Exception
     */
    public static function unzip(string $zipFile, string $to)
    {
        self::check();

        if (!file_exists($zipFile)) {
            throw new Exception(
                'Zip Archive ' . $zipFile . ' doesn\'t exist',
                404
            );
        }

        $Zip = new ZipArchive();

        if ($Zip->open($zipFile) === true) {
            $Zip->extractTo($to);
            $Zip->close();

            return;
        }

        throw new Exception('Error on Extract Zip Archive');
    }
}
