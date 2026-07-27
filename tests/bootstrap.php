<?php

$autoloader = require dirname(__DIR__).'/vendor/autoload.php';

// Explicitly register test namespace for PSR-4 autoloading
$autoloader->addPsr4('Playtini\\EasyAdminHelperBundle\\Tests\\', dirname(__DIR__).'/tests/');
