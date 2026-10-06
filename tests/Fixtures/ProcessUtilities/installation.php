<?php

use QUI\Utils\Installation;

require $argv[1];

define('CMS_DIR', $argv[2]);

$CountFiles = new ReflectionMethod(Installation::class, 'countAllFiles');
echo $CountFiles->invoke(null, true);
