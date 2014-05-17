<?php

define('ENV', 'dev');
//define('ENV', 'qa');
//define('ENV', 'live');

define('APP_PATH', __DIR__);

if (ENV == 'dev') {
    error_reporting(E_ALL || E_STRICT);
} else {
    error_reporting(0);
}

if (version_compare(PHP_VERSION, '5.4.0') <= 0) {
    header('Content-type: text/html');
    include(APP_PATH . '/phpversion.phtml');
    exit();
}

// core
require('./vendors/thcframe/core/core.php');
THCFrame\Core\Core::initialize();

// plugins
$path = APP_PATH . '/application/plugins';
$iterator = new \DirectoryIterator($path);

foreach ($iterator as $item) {
    if (!$item->isDot() && $item->isDir()) {
        include($path . '/' . $item->getFilename() . '/initialize.php');
    }
}

//module loading
$modules = array('App', 'Admin', 'Cron');
THCFrame\Core\Core::registerModules($modules);

$profiler = THCFrame\Profiler\Profiler::getProfiler();
$profiler->start();

// load services and run dispatcher
THCFrame\Core\Core::run();

$profiler->end();
