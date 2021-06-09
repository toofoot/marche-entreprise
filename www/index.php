<?php
define('BASE_PATH', realpath(dirname(dirname(__FILE__))));
define('APPLICATION_PATH', BASE_PATH . '/application');
define('LIB_PATH', BASE_PATH . '/library');
define('LOG_PATH', BASE_PATH . '/logs');
define('LAYOUT_PATH', BASE_PATH . '/layouts');
define('CONFIG_PATH', BASE_PATH . '/config');
define('TRANSLATE_PATH', BASE_PATH . '/translate');
define('CACHE_PATH',BASE_PATH . '/tmp');
define('UPLOAD_PATH',BASE_PATH . '/www/images/upload');

mb_internal_encoding("UTF-8");
set_include_path(implode(PATH_SEPARATOR, array( LIB_PATH,APPLICATION_PATH,get_include_path())));

       
// Define application environment
defined('APPLICATION_ENV')
    || define('APPLICATION_ENV',
              (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV')
                                         : 'prod'));
 
/** Zend_Application */
require_once 'Aurel/Application.php';
 
// Create application, bootstrap, and run

$application = new Aurel_Application( APPLICATION_ENV, CONFIG_PATH . DIRECTORY_SEPARATOR . 'config.ini');
$application->bootstrap()
            ->run();