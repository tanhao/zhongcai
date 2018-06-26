<?php
return array(
	// 数据库连接
	'DB_TYPE'   => 'mysql', // 数据库类型
	'DB_HOST'   => '127.0.0.1', // 服务器地址
	'DB_NAME'   => 'zhongcai', // 数据库名
	'DB_USER'   => 'root', // 用户名
	'DB_PWD'    => 'root', // 密码
	'DB_PORT'   => 3306, // 端口
	'DB_PREFIX' => 'zc_', // 数据库表前缀 
	// 框架系统配置
	"URL_MODEL" => 2,
	'MODULE_ALLOW_LIST' => array('Api','Adminbak','Cs','Cli','Mmbak','Ag','Agent'),
	'DEFAULT_MODULE'     => '', //默认模块
	'SHOW_PAGE_TRACE' =>false, // 显示页面Trace信息
	'URL_CASE_INSENSITIVE'=>false, //设置debug在关闭的时候，生成的url变成小写的问题
	// 项目名称
	"PROJECT_NAME" => "澳彩",
	"SITE_FULLNAME" => '澳彩',

	// 发邮件配置
	"MAIL_USER" 	=> "linweitao928@163.com",
	"MAIL_PASSWORD" => "linweitao8520345",
	// redis配置
	'REDIS_ADDRESS'		=> '127.0.0.1',
	'REDIS_PORT'		=> '6379',
	'REDIS_PASSWORD'	=> 'redis8888',
	// 模板常量
	'TMPL_PARSE_STRING'  => array(
         '__PUBLIC__' 	 => '/Static/Public',
         '__UPLOADS__'   => '/Static/Uploads'
    ),
    // 代理分钱模式：1-输赢都分配，2-赢才分配
	'PATTERN' => 1,
    // 安全KEY
    'SECRET_KEY'	=> '2b27690b4907bad30a67a59a383368cb',
    // 域名
    'DOMAIN' => '103.255.45.93',
    // 短信配置
    'SMS_ACCESS_KEY_ID'=> '',
    'SMS_ACCESS_KEY_SECRET'=> '',
    'SMS_SIGNATURE'=> '',
    'SMS_TEMPLATE_CODE'=> '',
);
