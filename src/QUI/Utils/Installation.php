<?php

/**
 * @author PCSG (Jan Wennrich)
 */

namespace QUI\Utils;

use FilesystemIterator;
use QUI\Cache\Manager;
use QUI\Exception;
use QUI\System\Log;
use QUI\Utils\System\Folder;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

use function iterator_count;

/**
 * Class Installation
 */
class Installation
{
    /** @var string Key used to store the amount of files in cache */
    const CACHE_KEY_FILE_COUNT = "installation_file_count";

    /** @var string Key used to store the timestamp of when the files where counted */
    const CACHE_KEY_FILE_COUNT_TIMESTAMP = "installation_file_count_timestamp";

    /**
     * Returns the size of the whole QUIQQER installation in bytes.
     * By default, the value is returned from cache.
     * If there is no value in cache, null is returned, unless you set the force parameter to true.
     * Only if you really need to get a freshly calculated result, you may set the force parameter to true.
     * When using the force parameter expect timeouts since the calculation could take a lot of time.
     *
     * @param boolean $force - Force a calculation of the QUIQQER installation folder's size. Values aren't returned from cache. Expect timeouts.
     *
     * @return int|null
     */
    public static function getWholeFolderSize(bool $force = false): ?int
    {
        if (!defined('CMS_DIR')) {
            return null;
        }

        return Folder::getFolderSize(CMS_DIR, $force);
    }

    /**
     * Returns the timestamp when the whole QUIQQER installation folder's size was stored in cache.
     * Returns null if there is no data in the cache.
     *
     * @return int|null
     */
    public static function getWholeFolderSizeTimestamp(): ?int
    {
        if (!defined('CMS_DIR')) {
            return null;
        }

        return Folder::getFolderSizeTimestamp(CMS_DIR);
    }


    /**
     * Returns how many files are inside the QUIQQER installation.
     * By default, the value is returned from cache.
     * If there is no value in cache, null is returned, unless you set the force parameter to true.
     * Only if you really need to get a freshly calculated result, you may set the force parameter to true.
     * When using the force parameter expect timeouts since the calculation could take a lot of time.
     *
     * @param boolean $force - Force a calculation of the folder's size. Values aren't returned from cache. Expect timeouts.
     *
     * @return float|int|string|null - The amount of files or null if no cached value is present
     */
    public static function getAllFileCount(bool $force = false): float | int | string | null
    {
        if ($force) {
            return self::countAllFiles();
        }

        if (!class_exists('QUI\Cache\Manager')) {
            return null;
        }

        try {
            $fileCount = Manager::get(self::CACHE_KEY_FILE_COUNT);
        } catch (Exception) {
            $fileCount = null;
        }

        return $fileCount;
    }

    /**
     * Returns the timestamp when the files were counted.
     * Returns null if there is no data in the cache.
     *
     * @return int|null
     */
    public static function getAllFileCountTimestamp(): ?int
    {
        if (!class_exists('QUI\Cache\Manager')) {
            return null;
        }

        try {
            $timestamp = Manager::get(self::CACHE_KEY_FILE_COUNT_TIMESTAMP);
        } catch (Exception) {
            $timestamp = null;
        }

        return $timestamp;
    }

    /**
     * Counts all files inside the QUIQQER installation folder
     *
     * @param boolean $doNotCache - Should the result be stored in cache?
     *
     * @return float|int|string|null
     */
    protected static function countAllFiles(bool $doNotCache = false): float | int | string | null
    {
        if (!defined('CMS_DIR')) {
            return null;
        }

        $fileCount = null;

        if (System::isSystemFunctionCallable('find') && System::isSystemFunctionCallable('wc')) {
            exec('find ' . CMS_DIR . ' -type f | wc -l', $output);

            if (isset($output[0]) && is_numeric($output[0])) {
                $fileCount = $output[0];
            }
        }

        if ($fileCount == null) {
            $fileCount = iterator_count(
                new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator(CMS_DIR, FilesystemIterator::SKIP_DOTS)
                )
            );
        }

        if ($doNotCache) {
            return $fileCount;
        }

        // Store the folder size and the current time as timestamp in cache
        try {
            if (class_exists('QUI\Cache\Manager')) {
                Manager::set(self::CACHE_KEY_FILE_COUNT, $fileCount);
                Manager::set(self::CACHE_KEY_FILE_COUNT_TIMESTAMP, time());
            }
        } catch (\Exception $Exception) {
            if (class_exists('QUI\System\Log')) {
                Log::writeException($Exception);
            }
        }

        return $fileCount;
    }

    /**
     * Returns the size of the installation's var/ folder in bytes.
     * If the first parameter is set to true the var/cache/ folder is excluded.
     *
     * By default, the value is returned from cache.
     * If there is no value in cache, null is returned, unless you set the force parameter to true.
     * Only if you really need to get a freshly calculated result, you may set the force parameter to true.
     * When using the force parameter expect timeouts since the calculation could take a lot of time.
     *
     * @param boolean $excludeCacheFolder - Exclude the var/cache/ folder from the result
     * @param boolean $force - Force a calculation of the folder's size. Values aren't returned from cache. Expect timeouts.
     *
     * @return int|null - The amount of files or null if no cached value is present
     */
    public static function getVarFolderSize(bool $excludeCacheFolder = false, bool $force = false): ?int
    {
        if (!defined('VAR_DIR')) {
            return null;
        }

        if (!class_exists('QUI\Cache\Manager')) {
            return null;
        }

        $size = Folder::getFolderSize(VAR_DIR, $force);

        if ($size && $excludeCacheFolder) {
            $size -= Manager::getCacheFolderSize($force);
        }

        return $size;
    }

    /**
     * Returns the timestamp when the whole QUIQQER installation folder's size was stored in cache.
     * Returns null if there is no data in the cache.
     *
     * @return int|null
     */
    public static function getVarFolderSizeTimestamp(): ?int
    {
        if (!defined('VAR_DIR')) {
            return null;
        }

        return Folder::getFolderSizeTimestamp(VAR_DIR);
    }
}
