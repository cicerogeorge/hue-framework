<?php
header('Content-type: text/html; charset=utf-8');

date_default_timezone_set('America/Recife');
setlocale(LC_TIME, 'pt_BR', 'pt_BR.utf-8', 'pt_BR.utf-8', 'portuguese');

// error reporting
ini_set('display_errors', 1);// 0 for production
ini_set('error_reporting', E_ALL);

// session handler
session_name('hahuh73ha1lkj1109d8sjaka');
session_start();

define('DS', DIRECTORY_SEPARATOR);
ob_start();

$includeFiles = array();
$includeFiles['routes']     = 'app'.DS.'config'.DS.'routes.php';
$includeFiles['autoloader'] = 'app'.DS.'config'.DS.'autoloader.php';
$includeFiles['globals']    = 'app'.DS.'config'.DS.'globals.php';

if (!file_exists($includeFiles['routes'])) {
    die('Routes file not found: ' . $includeFiles['routes']);
}
if (!file_exists($includeFiles['autoloader'])) {
    die('Autoloader file not found: ' . $includeFiles['autoloader']);
}
if (!file_exists($includeFiles['globals'])) {
    die('Globals file not found: ' . $includeFiles['globals']);
}

// include application stuff
if (!file_exists(__DIR__.DS.'app'.DS.'config'.DS.'config.json')) {
	die ('No config file found');
}
// define application constants
global $core_config;
$core_config = json_decode(file_get_contents(__DIR__.DS.'app'.DS.'config'.DS.'config.json'), true);
$env = $core_config['env'];
define('DIR', $core_config[$env]['dir']);
define('SYSTEM_NAME', $core_config['system_name']);
define('HOST', $core_config[$env]['database_host']);
define('DATABASE', $core_config[$env]['database_name']);
define('USERNAME',  $core_config[$env]['database_user']);
define('PASSWORD',  $core_config[$env]['database_pass']);
define('PREFIX',  $core_config[$env]['database_prefix']);
define('PORT',  $core_config[$env]['http_port']);
define('VERSION',  $core_config['version']);
define('LAST_UPDATE',  $core_config['last_update']);
define('LANGUAGE',  $core_config['language']);

include (__DIR__.DS.'app'.DS.'config'.DS.'mimes.php');
include (__DIR__.DS.'app'.DS.'config'.DS.'autoloader.php');
include (__DIR__.DS.'app'.DS.'config'.DS.'globals.php');
include (__DIR__.DS.'app'.DS.'config'.DS.'routes.php');

$DOCUMENT_ROOT = dirname(__FILE__);

define('DOCROOT', $DOCUMENT_ROOT);
define('WWWROOT', "http://".$_SERVER['SERVER_NAME'].PORT.DIR);
define('WEBROOT', WWWROOT."/webroot");

define('BODY', 'hold-transition skin-red sidebar-collapse sidebar-mini layout-boxed');

// load core classes
require(__DIR__.DS.'core'.DS.'Database.php');
require(__DIR__.DS.'core'.DS.'Loader.php');
require(__DIR__.DS.'core'.DS.'Route.php');
require(__DIR__.DS.'core'.DS.'App_Controller.php');
require(__DIR__.DS.'core'.DS.'App_Model.php');
// load core helpers
require(__DIR__.DS.'core'.DS.'helpers'.DS.'Inflector.php');
require(__DIR__.DS.'core'.DS.'helpers'.DS.'Form.php');
require(__DIR__.DS.'core'.DS.'helpers'.DS.'File.php');
require(__DIR__.DS.'core'.DS.'helpers'.DS.'Sql.php');

// load app helpers
if (count($autoloader['helpers'])) {
	foreach ($autoloader['helpers'] as $helper) {
		include (DOCROOT.DS.'app'.DS.'helpers'.DS.$helper.'_helper.php');
	}
}

// ROUTE SETTINGS
$rt = new Core\Route;
// apply default routes settings
$self = str_replace('index.php', '', $_SERVER['PHP_SELF']);
// $requestUri = str_replace($self, '', $_SERVER['REQUEST_URI']);
$requestUri = $self == '/' ? substr($_SERVER['REQUEST_URI'], 1) : str_replace($self, '', $_SERVER['REQUEST_URI']);
if (array_key_exists('QUERY_STRING', $_SERVER)) {
	$requestUri = str_replace('?'.$_SERVER['QUERY_STRING'], '', $requestUri);
}

$uri_array = explode('/', $requestUri);
$uri_key = $uri_array[0];
array_shift($uri_array);

if ($requestUri == '') { // default routes settings
	$rt->load($routes['default']);
}
else if (array_key_exists($uri_key, $routes)) { // personal routes settings
	$rt->load($routes[$uri_key], $uri_array);
}
else { // standard routes
	$rt->load($requestUri);
}