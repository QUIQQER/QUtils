<?php

/**
 * Autoloader
 */

spl_autoload_register(function ($className) {
    if (class_exists($className)) {
        return true;
    }

    $dir = str_replace('/phpunit', '', dirname(__FILE__));
    $file = $dir.'/lib/'.str_replace('\\', '/', $className).'.php';

    if (file_exists($file)) {
        require $file;

        return true;
    }
});