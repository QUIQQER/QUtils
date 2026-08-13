<?php

/**
 * This file contains Utils_Request_Url
 */

namespace QUI\Utils\Request;

use CurlHandle;
use QUI;
use QUI\Exception;

use function array_pop;
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
use function trim;

/**
 * Executes a request to a URL
 */


class Url
{
    /**
     * Get the Curl Object
     *
     * @param string $url - Url
     * @param array<array-key, mixed> $curlParams - Curl parameter
     *
     * @return CurlHandle
     * @throws Exception
     * @see http://www.php.net/manual/de/function.curl-setopt.php
     */
    public static function curl(string $url, array $curlParams = []): CurlHandle
    {
        $url = str_replace(' ', '+', $url); // URL Fix

        if ($url === '') {
            throw new Exception('URL must not be empty.');
        }

        $Curl = curl_init();

        if ($Curl === false) {
            throw new Exception('Could not initialize cURL.');
        }

        curl_setopt($Curl, CURLOPT_URL, $url);
        curl_setopt($Curl, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($Curl, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($Curl, CURLOPT_TIMEOUT, 10);

        foreach ($curlParams as $k => $v) {
            curl_setopt($Curl, $k, $v);
        }

        return $Curl;
    }

    /**
     * Get the content from an url
     *
     * @param string $url
     * @param array<array-key, mixed> $curlParams - see Utils_Request_Url::Curl (optional)
     *
     * @return string|bool
     * @throws Exception
     */
    public static function get(string $url, array $curlParams = []): string | bool
    {
        $Curl = self::curl($url, $curlParams);
        $data = self::exec($Curl);

        $error = curl_error($Curl);

        if ($error) {
            throw new Exception('Error at request: ' . $error . ' -> ' . $url);
        }

        return $data;
    }

    /**
     * Search the string at the content of the url
     *
     * @param string $url
     * @param string $search
     * @param array<array-key, mixed> $curlParams - see Utils_Request_Url::Curl (optional)
     *
     * @return boolean
     */
    public static function search(string $url, string $search, array $curlParams = []): bool
    {
        try {
            $content = self::get($url, $curlParams);
        } catch (Exception) {
            return false;
        }

        return str_contains((string)$content, $search);
    }

    /**
     * Get a header information of the url
     *
     * @param string $url
     * @param bool $info
     * @param array<array-key, mixed> $curlParams - see Utils_Request_Url::Curl (optional)
     *
     * @return mixed
     * @throws Exception
     */
    public static function getInfo(string $url, int | bool $info = false, array $curlParams = []): mixed
    {
        $Curl = self::curl($url, $curlParams);

        curl_exec($Curl);

        if (is_int($info)) {
            $result = curl_getinfo($Curl, $info);
        } else {
            $result = curl_getinfo($Curl);
        }

        $error = curl_error($Curl);

        if ($error) {
            throw new Exception('Error at request: ' . $error . ' -> ' . $url);
        }

        return $result;
    }

    /**
     * exec the curl object
     *
     * @param CurlHandle $Curl
     *
     * @return bool|string
     */
    public static function exec(CurlHandle $Curl): bool | string
    {
        if (ini_get('open_basedir') == '' && ini_get('safe_mode') == 'Off') {
            curl_setopt($Curl, CURLOPT_FOLLOWLOCATION, false);

            $newUrl = (string)curl_getinfo($Curl, CURLINFO_EFFECTIVE_URL);
            $rch = curl_copy_handle($Curl);
            $mr = 10;

            if ($rch === false) {
                return curl_exec($Curl);
            }

            if ($newUrl === '') {
                return curl_exec($Curl);
            }

            curl_setopt($rch, CURLOPT_HEADER, true);
            curl_setopt($rch, CURLOPT_NOBODY, true);
            curl_setopt($rch, CURLOPT_FORBID_REUSE, false);
            curl_setopt($rch, CURLOPT_RETURNTRANSFER, true);

            do {
                if ($newUrl === '') {
                    break;
                }

                curl_setopt($rch, CURLOPT_URL, $newUrl);
                $header = (string)curl_exec($rch);

                if (curl_errno($rch)) {
                    $code = 0;
                } else {
                    $code = curl_getinfo($rch, CURLINFO_HTTP_CODE);

                    if ($code == 301 || $code == 302) {
                        if (preg_match('/Location:(.*?)\n/', $header, $matches) === 1) {
                            $newUrl = trim((string)array_pop($matches));
                        } else {
                            $code = 0;
                        }
                    } else {
                        $code = 0;
                    }
                }
            } while ($code && --$mr);

            unset($rch);
            if ($newUrl !== '') {
                curl_setopt($Curl, CURLOPT_URL, $newUrl);
            }
        }

        return curl_exec($Curl);
    }

    /**
     * Returns if a given URL is reachable.
     * Reachable means that the return code equals 200.
     *
     * @param string $url
     *
     * @return bool
     */
    public static function isReachable($url): bool
    {
        $curlParams = [
            CURLOPT_HEADER => true,
            CURLOPT_NOBODY => true,
            CURLOPT_FOLLOWLOCATION => true
        ];

        try {
            $returnCode = QUI\Utils\Request\Url::getInfo($url, CURLINFO_HTTP_CODE, $curlParams);
        } catch (Exception) {
            return false;
        }

        return $returnCode == 200;
    }
}
