<?php

/**
 * @author PCSG (Jan Wennrich)
 */

namespace QUI\Utils\System;

use FilesystemIterator;
use QUI\Cache\Exception;
use QUI\Cache\Manager;
use QUI\System\Log;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;

use function file_exists;
use function ini_get;
use function realpath;
use function set_time_limit;

/**
 * Class Folder
 */
class Folder
{
    /**
     * Returns the size of the given folder in bytes.
     * By default, the value is returned from cache.
     * If there is no value in cache, null is returned, unless you set the force parameter to true.
     * Only if you really need to get a freshly calculated result, you may set the force parameter to true.
     * When using the force parameter expect timeouts since the calculation could take a lot of time.
     *
     * @param string $path - The folder's path
     * @param boolean $force - Force a calculation of the folder's size. Values aren't returned from cache. Expect timeouts.
     *
     * @return int|null - The folder's size in bytes
     */
    public static function getFolderSize(string $path, bool $force = false): ?int
    {
        // Don't return value from cache, calculate a fresh folder size
        if ($force) {
            return self::calculateFolderSize($path);
        }

        // Return the value from cache
        try {
            return Manager::get(self::getFolderSizeCacheKey($path));
        } catch (Exception) {
            // If there is no value in cache, return null, since 0 could be an empty folder.
            return null;
        }
    }

    /**
     * Returns the timestamp when the folder's size was stored in cache.
     * Returns null if there is no data in the cache.
     *
     * @param string $path - The folder's path
     *
     * @return int|null
     */
    public static function getFolderSizeTimestamp(string $path): ?int
    {
        try {
            $timestamp = Manager::get(self::getFolderSizeTimestampCacheKey($path));
        } catch (Exception) {
            $timestamp = null;
        }

        return $timestamp;
    }

    /**
     * Calculates and returns the size of the package folder in bytes.
     * The result is also stored in cache by default. Set the doNotCache parameter to true to prevent this.
     *
     * This process may take a lot of time
     *
     * @param string $path - The folder's path
     * @param boolean $doNotCache - Don't store the result in cache. Off by default.
     *
     * @return int
     */
    protected static function calculateFolderSize(string $path, bool $doNotCache = false): int
    {
        $path = self::sanitizePath($path);
        $folderSize = 0;
        $maxExecTime = ini_get('max_execution_time');

        // Sum up all file sizes
        if ($path !== '' && file_exists($path)) {
            $Iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(
                    $path,
                    FilesystemIterator::SKIP_DOTS
                ),
                RecursiveIteratorIterator::SELF_FIRST,
                RecursiveIteratorIterator::CATCH_GET_CHILD
            );

            foreach ($Iterator as $Object) {
                /** @var RecursiveDirectoryIterator $Object */
                try {
                    // To prevent timeouts we always reset the time limit to two seconds
                    set_time_limit(2);
                    $folderSize += $Object->getSize();
                } catch (RuntimeException $RuntimeException) {
                    // If getSize() fails (e.g. at broken symlinks) we get here
                    continue;
                }
            }

            // Reset the time limit to its default value.
            // This ensures that following code execution doesn't time out after two seconds.
            set_time_limit($maxExecTime);
        }

        if ($doNotCache) {
            return $folderSize;
        }

        // Store the folder size and the current time as timestamp in cache
        try {
            Manager::set(self::getFolderSizeCacheKey($path), $folderSize);
            Manager::set(self::getFolderSizeTimestampCacheKey($path), time());
        } catch (\Exception $Exception) {
            Log::writeException($Exception);
        }

        return $folderSize;
    }

    /**
     * Sanitizes a path string.
     * E.g: removing "../" and symbolic links or adding a slash to the end
     *
     * @param string $path - The path to sanitize
     *
     * @return string
     */
    protected static function sanitizePath(string $path): string
    {
        // Add slash to the end of the path if it's not present
        if (!str_ends_with($path, '/')) {
            $path .= '/';
        }

        // Canonicalize the path
        return realpath($path);
    }

    /**
     * Generates the cache key under which the folder's size is stored.
     *
     * @param string $path - The folder's path
     * @return string - The generated cache key
     */
    protected static function getFolderSizeCacheKey(string $path): string
    {
        $path = self::sanitizePath($path);

        return "folder_size_" . sha1($path);
    }

    /**
     * Generates the cache key under which the folder's size timestamp is stored.
     *
     * @param string $path - The folder's path
     * @return string - The generated cache key
     */
    protected static function getFolderSizeTimestampCacheKey(string $path): string
    {
        $path = self::sanitizePath($path);

        return "folder_size_timestamp_" . sha1($path);
    }
}
