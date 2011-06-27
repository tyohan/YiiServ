#!/usr/bin/php -Cq
<?php
ini_set('mbstring.func_overload', '0');
ini_set('output_handler', '');
error_reporting(E_ALL | E_STRICT);
@ob_end_flush();
set_time_limit(0);
date_default_timezone_set('Asia/Jakarta');
// change the following paths if necessary
$yii=dirname(__FILE__).'/../yii/1.1.7/yii.php';
$config=dirname(__FILE__).'/protected/config/main.php';

// remove the following lines when in production mode
defined('YII_DEBUG') or define('YII_DEBUG',true);
// specify how many levels of call stack should be shown in each log message
defined('YII_TRACE_LEVEL') or define('YII_TRACE_LEVEL',3);

$socketDaemon=dirname(__FILE__).'/protected/extensions/yiiserv/YiiSocketDaemon.php';
include $socketDaemon;
$daemon = new YiiSocketDaemon();
$server = $daemon->create_server('YiiServ', 'YiiServClient',$yii,$config, 0, 8080);
$daemon->process();