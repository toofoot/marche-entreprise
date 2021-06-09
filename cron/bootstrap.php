<?php
/**
 * www.pronoteam.fr
 * 
 * @author  Aurélien Cornu
 * @version 1.0
 */
define('BASE_PATH',realpath(dirname(dirname(__FILE__))));
define('LIB_PATH', realpath(dirname(dirname(__FILE__)) . '/library'));
define('APP_PATH', realpath(dirname(dirname(__FILE__)) . '/application'));
define('CONFIG_PATH', BASE_PATH . '/config');

set_include_path(implode(PATH_SEPARATOR,array(LIB_PATH,APP_PATH,get_include_path())));

require_once "Zend/Application.php"; 

// Create application, bootstrap, and run
$env = $argv[1];
//$env = 'molly-handball-fr';

$application = new Zend_Application( $env, dirname(__FILE__) . '/config.ini');
$application->bootstrap('db');
$application->bootstrap('mail');
            
error_reporting(E_ALL | E_STRICT);  
ini_set('display_startup_errors', 1);  
ini_set('display_errors', 1);
date_default_timezone_set('Europe/Paris');

$config = Aurel_Table_Config::getConfig();
?>

