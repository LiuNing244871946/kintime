<?php
    return  array(
    'DB_TYPE'   =>  'mysql',             // 数据库类型
    'DB_HOST'   =>  '192.168.1.121',     // 服务器地址
    'DB_NAME'   =>  'kintime-new',       // 数据库名
    'DB_USER'   =>  'kintime',           // 用户名
    'DB_PWD'    =>  '111222',            // 密码
    'DB_PORT'   =>   3306 ,              // 端口
    'DB_CHARSET'=>  'utf8',
    'DB_PREFIX' =>  'bao_',              // 数据库表前缀
    'AUTH_KEY'  =>  'faf461459f7e87f09f729ee3227060f1', //这个KEY只是保证部分表单在没有SESSION 的情况下判断用户本人操作的作用
    'BAO_KEY'   => '',
    
);