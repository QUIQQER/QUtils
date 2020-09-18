<?php

/**
 * This file contains \QUI\Archiver\Zip
 */

namespace QUI\Archiver;

use QUI;

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
     */
    public function __construct()
    {
        self::check();
    }

    /**
     * Check, if ZipArchive is enabled
     *
     * @return boolean
     * @throws \QUI\Exception
     */
    public static function check()
    {
        if (!\class_exists('ZipArchive')) {
            throw new QUI\Exception(
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
     * @param string $zipfile - Name of new Zipfiles
     * @param array $ignore - Folder to be ignored
     *
     * @throws QUI\Exception
     */
    public static function zip($folder, $zipfile, $ignore = [])
    {
        self::check();

        $Zip = new \ZipArchive();

        if ($Zip->open($zipfile, \ZIPARCHIVE::CREATE) !== true) {
            throw new QUI\Exception('cannot open '.$zipfile);
        }

        if (!\is_array($ignore)) {
            $ignore = [];
        }

        if (\substr($folder, -1) != '/') {
            $folder .= '/';
        }

        $File  = new QUI\Utils\System\File();
        $files = $File->readDirRecursiv($folder);

        foreach ($files as $_folder => $_file) {
            if (!empty($ignore) && in_array($_folder, $ignore)) {
                continue;
            }

            $oldfolder = $folder.$_folder;

            for ($i = 0, $len = \count($_file); $i < $len; $i++) {
                if (\file_exists($oldfolder.$_file[$i])) {
                    $Zip->addFile(
                        $oldfolder.$_file[$i],
                        $_folder.$_file[$i]
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
     * @param string $zipfile - Path to the zip file (folders have to already exist)
     *
     * @throws QUI\Exception
     */
    public static function zipFiles($files, $zipfile)
    {
        self::check();

        $Zip = new \ZipArchive();

        if ($Zip->open($zipfile, \ZIPARCHIVE::CREATE) !== true) {
            throw new QUI\Exception('cannot open '.$zipfile);
        }

        if (!is_array($files) || count($files) == 0) {
            throw new QUI\Exception("You need to specify at least one file to zip in an array");
        }

        foreach ($files as $file) {
            if (\file_exists($file)) {
                $Zip->addFile($file, \basename($file));
            }
        }

        $Zip->close();
    }


    /**
     * Unzip the file
     *
     * @param string $zipfile - path to zip file
     * @param string $to - path to the destination folder
     *
     * @throws \QUI\Exception
     */
    public static function unzip($zipfile, $to)
    {
        self::check();

        if (!\file_exists($zipfile)) {
            throw new QUI\Exception(
                'Zip Archive '.$zipfile.' doesn\'t exist',
                404
            );
        }

        $Zip = new \ZipArchive();

        if ($Zip->open($zipfile) === true) {
            $Zip->extractTo($to);
            $Zip->close();

            return;
        }

        throw new QUI\Exception('Error on Extract Zip Archive');
    }
}
