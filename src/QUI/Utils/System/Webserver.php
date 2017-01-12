<?php


namespace QUI\Utils\System;

use QUI\Exception;
use QUI\Utils\System;

class Webserver
{

    const WEBSERVER_APACHE = 0;
    const WEBSERVER_NGINX = 0;


    public static function detectInstalledWebserver()
    {
        try {
            return self::detectInstalledWebserverHeader();
        } catch (Exception $Exception) {
        }

        try {
            return self::detectInstalledWebserverCLI();
        } catch (Exception $Exception) {
        }

        throw new Exception("Could not detect the installed Webserver");
    }



    // ====================================== //

    /**
     * Attempts to detect the webserver through HTTP Headers.
     *
     * @return int - @see QUI\Utils\System\Webserver::WEBSERVER_APACHE; @see QUI\Utils\System\Webserver::WEBSERVER_NGINX
     * @throws Exception
     */
    protected static function detectInstalledWebserverHeader()
    {
        if (!isset($_SERVER) || !isset($_SERVER['SERVER_SOFTWARE']) || empty($_SERVER['SERVER_SOFTWARE'])) {
            throw new Exception("Could not retrieve Serverdata");
        }

        $server = $_SERVER['SERVER_SOFTWARE'];
        if (strpos($server, "apache") !== false) {
            return self::WEBSERVER_APACHE;
        }

        if (strpos($server, "nginx") !== false) {
            return self::WEBSERVER_NGINX;
        }

        throw new Exception("Could not retrieve Serverdata");
    }

    /**
     * Attempts to detect the installed Webserver via CLI
     *
     * @return int - @see QUI\Utils\System\Webserver::WEBSERVER_APACHE; @see QUI\Utils\System\Webserver::WEBSERVER_NGINX
     * @throws Exception
     */
    protected static function detectInstalledWebserverCLI()
    {
        if (!System::isShellFunctionEnabled("shell_exec")) {
            throw new Exception("Could not retrieve Serverdata");
        }

        if (!empty(shell_exec("which apache2"))) {
            return self::WEBSERVER_APACHE;
        }

        if (!empty(shell_exec("which nginx"))) {
            return self::WEBSERVER_NGINX;
        }

        throw new Exception("Could not retrieve Serverdata");
    }
}
