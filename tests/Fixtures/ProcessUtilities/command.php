<?php

use QUI\Utils\System;

require $argv[1];

echo System::isSystemFunctionCallable('echo') ? 'available' : 'unavailable';
