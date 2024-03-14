<?php

namespace QUI\Utils\System;

use QUI\Exception;
use QUI\Utils\System;

use function apache_get_version;
use function explode;
use function function_exists;
use function preg_match;
use function shell_exec;

/**
 * Class Webserver
 */
class Webserver
{
    /** Constant for Apache2 Webserver */
    const WEBSERVER_APACHE = 0;

    /** Constant for NGINX Webserver */
    const WEBSERVER_NGINX = 1;

    /**
     * Attempts to detect the installed/used Webserver.
     *
     * @return int @see QUI\Utils\System\Webserver::WEBSERVER_APACHE; @see QUI\Utils\System\Webserver::WEBSERVER_NGINX
     * @throws Exception
     */
    public static function detectInstalledWebserver(): int
    {
        try {
            return self::detectInstalledWebserverHeader();
        } catch (Exception) {
        }

        try {
            return self::detectInstalledWebserverCLI();
        } catch (Exception) {
        }

        throw new Exception("Could not detect the installed Webserver");
    }

    /**
     * Attempts to detect the Apache webservers version
     * Return format: array("major","minor","point")
     *
     * @return array
     * @throws Exception
     */
    public static function detectApacheVersion(): array
    {
        # Attempt detection by apache2 module
        if (function_exists('apache_get_version')) {
            $version = apache_get_version();
            $regex = "/Apache\\/([0-9\\.]*)/i";
            $res = preg_match($regex, $version, $matches);

            if ($res && isset($matches[1])) {
                $version = $matches[1];
                return explode(".", $version);
            }
        }

        # Attempt detection by system shell
        if (System::isShellFunctionEnabled("shell_exec") && !empty(shell_exec("which apache2"))) {
            $version = shell_exec('apache2 -v');
            $regex = "/Apache\\/([0-9\\.]*)/i";
            $res = preg_match($regex, $version, $matches);
            if ($res && isset($matches[1])) {
                $version = $matches[1];

                return explode(".", $version);
            }
        }

        throw new Exception("Could not detect Apache Version");
    }

    /**
     * Attempts to detect the webserver through HTTP Headers.
     *
     * @return int - @see QUI\Utils\System\Webserver::WEBSERVER_APACHE; @see QUI\Utils\System\Webserver::WEBSERVER_NGINX
     * @throws Exception
     */
    protected static function detectInstalledWebserverHeader(): int
    {
        if (!empty($_SERVER['SERVER_SOFTWARE'])) {
            throw new Exception("Could not retrieve server data");
        }

        $server = $_SERVER['SERVER_SOFTWARE'];

        if (str_contains($server, "apache")) {
            return self::WEBSERVER_APACHE;
        }

        if (str_contains($server, "nginx")) {
            return self::WEBSERVER_NGINX;
        }

        throw new Exception("Could not retrieve server data");
    }

    /**
     * Attempts to detect the installed Webserver via CLI
     *
     * @return int - @see QUI\Utils\System\Webserver::WEBSERVER_APACHE; @see QUI\Utils\System\Webserver::WEBSERVER_NGINX
     * @throws Exception
     */
    protected static function detectInstalledWebserverCLI(): int
    {
        if (!System::isShellFunctionEnabled("shell_exec")) {
            throw new Exception("Could not retrieve server data");
        }

        if (!empty(shell_exec("which apache2"))) {
            return self::WEBSERVER_APACHE;
        }

        if (!empty(shell_exec("which nginx"))) {
            return self::WEBSERVER_NGINX;
        }

        throw new Exception("Could not retrieve server data");
    }
}
