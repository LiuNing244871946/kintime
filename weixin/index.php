<?php

if (ini_get('magic_quotes_gpc')) {
	function stripslashesRecursive(array $array){
		foreach ($array as $k => $v) {
			if (is_string($v)){
				$array[$k] = stripslashes($v);
			} else if (is_array($v)){
				$array[$k] = stripslashesRecursive($v);
			}
		}
		return $array;
	}
	$_GET = stripslashesRecursive($_GET);
	$_POST = stripslashesRecursive($_POST);
}

define('BASE_PATH' ,getcwd().'/..');

define('GROUP_NAME','Weixin');
//调试模式
define('APP_DEBUG',true);
//定义项目名称
define('APP_NAME', 'Baocms');
define('NOW_TIME',time());
//定义项目路径
define('APP_PATH', BASE_PATH.'/Baocms/');
//加载框架入文件
require BASE_PATH.'/ThinkPHP/ThinkPHP.php';