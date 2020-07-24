<?php

// file: app/config/globals.php
global $CONFIG;
global $core_errors;

$CONFIG = $core_errors = array();
// inits all the stuff
// development settings
$CONFIG['app']['debug'] = false;
$CONFIG['app']['env'] = 'production';
// form settings
$CONFIG['form'] = false;
// system settings
$CONFIG['system']['numrq'] = 0;

// default messages
$CONFIG['msg'] = array();
$CONFIG['msg']['success'] = array(); // green
$CONFIG['msg']['error'] = array(); // red
$CONFIG['msg']['alert'] = array(); // yellow
$CONFIG['msg']['info'] = array(); // blues
// mime types
$CONFIG['mimes'] = $mimes;

global $urlParams;
global $__get;
global $__post;
global $__server;
$urlParams = array();

/*
 * Receives filtered $_GET input
 * @var Array
 */
$__get = filter_input_array(INPUT_GET);
if ($__get === null) {
    $__get = array();
}

/*
 * Receives filtered $_POST input
 * @var Array
 */
$__post = filter_input_array(INPUT_POST);
if ($__post === null) {
    $__post = array();
}

/*
 * Receives filtered $_SERVER input
 * @var Array
 */
$__server = filter_input_array(INPUT_SERVER);
if ($__server === null) {
    $__server = array();
}

//$CONFIG['form'] = isset($__post['form']) ? $__post['form'] : \FALSE;