<?php

/**
 * This file contains Utils_Request_Url
 */

namespace QUI\Utils\Request;

use QUI;

/**
 * Executes a request to a URL
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.utils.request
 */
class Url
{
    /**
     * List of curl Objects
     *
     * @var array
     */
    public static $Curls = [];

    /**
     * Get the Curl Objekt
     *
     * @param string $url - Url
     * @param array $curlparams - Curl parameter
     *
     * @return resource
     * @see http://www.php.net/manual/de/function.curl-setopt.php
     */
    public static function curl($url, $curlparams = [])
    {
        $url  = str_replace(' ', '+', $url); // URL Fix
        $hash = md5(serialize($url).serialize($curlparams));

        if (isset(self::$Curls[$hash]) && self::$Curls[$hash]) {
            return self::$Curls[$hash];
        }

        $Curl = curl_init();
        curl_setopt($Curl, CURLOPT_URL, $url);
        curl_setopt($Curl, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($Curl, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($Curl, CURLOPT_TIMEOUT, 10);

        foreach ($curlparams as $k => $v) {
            curl_setopt($Curl, $k, $v);
        }

        self::$Curls[$url] = $Curl;

        return $Curl;
    }

    /**
     * Get the content from a url
     *
     * @param string $url
     * @param array $curlparams - see Utils_Request_Url::Curl (optional)
     *
     * @return mixed
     * @throws \QUI\Exception
     */
    public static function get($url, $curlparams = [])
    {
        $Curl = self::curl($url, $curlparams);
        $data = self::exec($Curl);

        $error = curl_error($Curl);

        if ($error) {
            throw new QUI\Exception('Fehler bei der Anfrage '.$error);
        }

        curl_close($Curl);

        return $data;
    }

    /**
     * Search the string at the content of the url
     *
     * @param string $url
     * @param string $search
     * @param array $curlparams - siehe Utils_Request_Url::Curl (optional)
     *
     * @return boolean
     */
    public static function search($url, $search, $curlparams = [])
    {
        try {
            $content = self::get($url, $curlparams);
        } catch (QUI\Exception $Exception) {
            return false;
        }

        return strpos($content, $search) === false ? false : true;
    }

    /**
     * Get a header information of the url
     *
     * @param string $url
     * @param bool $info
     * @param array $curlparams - see Utils_Request_Url::Curl (optional)
     *
     * @return mixed
     * @throws \QUI\Exception
     */
    public static function getInfo($url, $info = false, $curlparams = [])
    {
        $Curl = self::curl($url, $curlparams);

        curl_exec($Curl);

        if ($info) {
            $result = curl_getinfo($Curl, $info);
        } else {
            $result = curl_getinfo($Curl);
        }

        $error = curl_error($Curl);

        if ($error) {
            throw new QUI\Exception('Fehler bei der Anfrage '.$error);
        }

        curl_close($Curl);

        return $result;
    }

    /**
     * exec the curl object
     *
     * @param resource $Curl
     *
     * @return mixed
     */
    public static function exec($Curl)
    {
        if (ini_get('open_basedir') == '' && ini_get('safe_mode') == 'Off') {
            curl_setopt($Curl, CURLOPT_FOLLOWLOCATION, false);

            $newurl = curl_getinfo($Curl, CURLINFO_EFFECTIVE_URL);
            $rch    = curl_copy_handle($Curl);

            curl_setopt($rch, CURLOPT_HEADER, true);
            curl_setopt($rch, CURLOPT_NOBODY, true);
            curl_setopt($rch, CURLOPT_FORBID_REUSE, false);
            curl_setopt($rch, CURLOPT_RETURNTRANSFER, true);

            do {
                curl_setopt($rch, CURLOPT_URL, $newurl);
                $header = curl_exec($rch);

                if (curl_errno($rch)) {
                    $code = 0;
                } else {
                    $code = curl_getinfo($rch, CURLINFO_HTTP_CODE);

                    if ($code == 301 || $code == 302) {
                        preg_match('/Location:(.*?)\n/', $header, $matches);
                        $newurl = trim(array_pop($matches));
                    } else {
                        $code = 0;
                    }
                }
            } while ($code && --$mr);

            curl_close($rch);
            curl_setopt($Curl, CURLOPT_URL, $newurl);
        }

        return curl_exec($Curl);
    }
}
