<?php

//当前目录 非THINKPHP框架需要的路径

define('BASE_PATH', getcwd());
if (ini_get('magic_quotes_gpc')) {

    function stripslashesRecursive(array $array) {
        foreach ($array as $k => $v) {
            if (is_string($v)) {
                $array[$k] = stripslashes($v);
            } else if (is_array($v)) {
                $array[$k] = stripslashesRecursive($v);
            }
        }
        return $array;
    }

    $_GET = stripslashesRecursive($_GET);
    $_POST = stripslashesRecursive($_POST);
}

//调试模式
ini_set('display_errors','On');
error_reporting(0);
define('APP_DEBUG', true);
//定义项目名称
define('APP_NAME', 'Kintime');
ini_set('date.timezone', 'Asia/Shanghai');
define('TODAY', date('Y-m-d', $_SERVER['REQUEST_TIME']));
define('NOW', date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME']));
//定义项目路径
define('APP_PATH', BASE_PATH . '/Kintime/');
// header("Power by: Kintime;");
header("Content-type: text/html; charset=utf-8");
//加载框架入文件
require './ThinkPHP/ThinkPHP.php';