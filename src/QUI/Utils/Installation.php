<?php

/**
 * @author PCSG (Jan Wennrich)
 */

namespace QUI\Utils;

use QUI\Utils\System\Folder;

/**
 * Class Installation
 * @package QUI\Utils
 */
class Installation
{
    /**
     * Returns the size of the whole QUIQQER installation in bytes.
     * By default the value is returned from cache.
     * If there is no value in cache, null is returned, unless you set the force parameter to true.
     * Only if you really need to get a freshly calculated result, you may set the force parameter to true.
     * When using the force parameter expect timeouts since the calculation could take a lot of time.
     *
     * @param boolean $force - Force a calculation of the QUIQQER installation folder's size. Values aren't returned from cache. Expect timeouts.
     *
     * @return int
     */
    public static function getWholeFolderSize($force = false)
    {
        return Folder::getFolderSize(CMS_DIR, $force);
    }

    /**
     * Returns the timestamp when the whole QUIQQER installation folder's size was stored in cache.
     * Returns null if there is no data in the cache.
     *
     * @return int|null
     */
    public static function getWholeFolderSizeTimestamp()
    {
        return Folder::getFolderSizeTimestamp(CMS_DIR);
    }


    public static function countAllFiles($force = false)
    {
        // TODO: implementieren
    }
}
