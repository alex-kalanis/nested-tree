<?php

define('AUTHOR_NAME', 'kalanis');
define('PROJECT_NAME', 'nested_tree');
define('PROJECT_DIR', 'php-src');

$composter = realpath(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php');
if ($composter) {
    $loader = @require_once $composter;
    //    $loader->addPsr4(implode('\\', [AUTHOR_NAME, PROJECT_NAME]), __DIR__);
}

require_once __DIR__ . DIRECTORY_SEPARATOR . '_autoload.php';
