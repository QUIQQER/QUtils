<?php

/**
 * This file contains Utils_Request_Url
 */

namespace QUI\Utils\Request;

use QUI;
use QUI\Exception;

use function array_pop;
use function curl_close;
use function curl_copy_handle;
use function curl_errno;
use function curl_error;
use function curl_exec;
use function curl_getinfo;
use function curl_init;
use function curl_setopt;
use function ini_get;
use function preg_match;
use function str_replace;
use function strpos;
use function trim;

/**
 * Executes a request to a URL
 *
 * @author  www.pcsg.de (Henning Leutz)
 */
class Url
{
    /**
     * List of curl Objects
     *
     * @var array
     */
    public static array $Curls = [];

    /**
     * Get the Curl Objekt
     *
     * @param string $url - Url
     * @param array $curlparams - Curl parameter
     *
     * @return resource
     * @see http://www.php.net/manual/de/function.curl-setopt.php
     */
    public static function curl(string $url, array $curlparams = [])
    {
        $url  = str_replace(' ', '+', $url); // URL Fix
        $Curl = curl_init();
        curl_setopt($Curl, CURLOPT_URL, $url);
        curl_setopt($Curl, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($Curl, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($Curl, CURLOPT_TIMEOUT, 10);

        foreach ($curlparams as $k => $v) {
            curl_setopt($Curl, $k, $v);
        }

        return $Curl;
    }

    /**
     * Get the content from a url
     *
     * @param string $url
     * @param array $curlParams - see Utils_Request_Url::Curl (optional)
     *
     * @return mixed
     * @throws Exception
     */
    public static function get(string $url, array $curlParams = [])
    {
        $Curl = self::curl($url, $curlParams);
        $data = self::exec($Curl);

        $error = curl_error($Curl);

        if ($error) {
            throw new Exception('Fehler bei der Anfrage ' . $error);
        }

        curl_close($Curl);

        return $data;
    }

    /**
     * Search the string at the content of the url
     *
     * @param string $url
     * @param string $search
     * @param array $curlParams - siehe Utils_Request_Url::Curl (optional)
     *
     * @return boolean
     */
    public static function search(string $url, string $search, array $curlParams = []): bool
    {
        try {
            $content = self::get($url, $curlParams);
        } catch (Exception $Exception) {
            return false;
        }

        return !(strpos($content, $search) === false);
    }

    /**
     * Get a header information of the url
     *
     * @param string $url
     * @param bool $info
     * @param array $curlParams - see Utils_Request_Url::Curl (optional)
     *
     * @return mixed
     * @throws Exception
     */
    public static function getInfo(string $url, bool $info = false, array $curlParams = [])
    {
        $Curl = self::curl($url, $curlParams);

        curl_exec($Curl);

        if ($info) {
            $result = curl_getinfo($Curl, $info);
        } else {
            $result = curl_getinfo($Curl);
        }

        $error = curl_error($Curl);

        if ($error) {
            throw new Exception('Fehler bei der Anfrage ' . $error);
        }

        curl_close($Curl);

        return $result;
    }

    /**
     * exec the curl object
     *
     * @param resource $Curl
     *
     * @return bool|string
     */
    public static function exec($Curl)
    {
        if (ini_get('open_basedir') == '' && ini_get('safe_mode') == 'Off') {
            curl_setopt($Curl, CURLOPT_FOLLOWLOCATION, false);

            $newUrl = curl_getinfo($Curl, CURLINFO_EFFECTIVE_URL);
            $rch    = curl_copy_handle($Curl);

            curl_setopt($rch, CURLOPT_HEADER, true);
            curl_setopt($rch, CURLOPT_NOBODY, true);
            curl_setopt($rch, CURLOPT_FORBID_REUSE, false);
            curl_setopt($rch, CURLOPT_RETURNTRANSFER, true);

            do {
                curl_setopt($rch, CURLOPT_URL, $newUrl);
                $header = curl_exec($rch);

                if (curl_errno($rch)) {
                    $code = 0;
                } else {
                    $code = curl_getinfo($rch, CURLINFO_HTTP_CODE);

                    if ($code == 301 || $code == 302) {
                        preg_match('/Location:(.*?)\n/', $header, $matches);
                        $newUrl = trim(array_pop($matches));
                    } else {
                        $code = 0;
                    }
                }
            } while ($code && --$mr);

            curl_close($rch);
            curl_setopt($Curl, CURLOPT_URL, $newUrl);
        }

        return curl_exec($Curl);
    }

    /**
     * Returns if a given URL is reachable.
     * Reachable means that the return code equals 200.
     *
     * @param $url
     *
     * @return bool
     */
    public static function isReachable($url): bool
    {
        $curlParams = [
            CURLOPT_HEADER         => true,
            CURLOPT_NOBODY         => true,
            CURLOPT_FOLLOWLOCATION => true
        ];

        try {
            $returnCode = QUI\Utils\Request\Url::getInfo($url, CURLINFO_HTTP_CODE, $curlParams);
        } catch (Exception $exception) {
            return false;
        }

        return $returnCode == 200;
    }
}
