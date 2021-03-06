<?php

/**
 +------------------------------------------------------------------------------
 * PLFrame 系统默认配置文件
 +------------------------------------------------------------------------------
 * @category PLFrame
 * @package  Common
 * @author   pengzl<pengzl_gz@163.com>
 * @version  $Id: convention.php 1 2014-09-11 02:12:40Z pengzl $
 +------------------------------------------------------------------------------
 */
if (!defined('PLFRAME_PATH'))
    exit(0);
return array(
    /* 数据库设置 */
    'DB_PATH' => '/lib/Db/', // DB库路径
    'DB_TYPE' => '', // 数据库类型
    'DB_HOST' => 'localhost', // 数据库地址
    'DB_NAME' => 'db_PLFrame', // 数据库名
    'DB_USER' => 'root', // 库用户名
    'DB_PWD' => '', // 库用户密码
    'DB_PORT' => '3306', // MYSQL端口
    'DB_CHARSET' => 'utf8', // 数据库编码默认采用utf8
    'CREATE_DATABASE' => True, // 自动创建数据库
    'LOGIN_CHECK_ARR' => '|', // 用SESSION验证页面配置 数组格式  模块ID|action名称   例:注册页面   user|register
    'LOGIN_AUTO_CHECK' => false, // 是否开启页面登陆验证
    'USER_SESSION_KEY' => '_PLFRAME_ID_', // SESSION用户ID标识
    'USER_SESSION_ACTION' => '_CHECK_ACTION_ID_', // SESSION action ID标识
    'USER_SESSION_ACTION_NAME' => '_CHECK_ACTION_NAME_', // SESSION action名标识
    'USER_SESSION_SEP' => ',', // SESSION 字串分隔符标识
    'USER_AUTHCODE_MOD_SUMT' => 0, // SESSION 用户模块权限总值
    'USER_AUTHCODE_OPT_SUMT' => 0, // SESSION 用户操作权限总值
    'SYS_AUTHCODE_KEY' => '0f762431322c0d11c105d3580f784ef8', // 默认字串加解密KEY
    'SYS_AUTHCODE_TIME_LIMIT' => 0, // 密串有效时长(秒) 0表示一直有效
    'CHECK_ACTION_ID' => 'actionID', // action ID标识 即页面对应ID编号标识 用于模块权限验证 模块权限验证页面编号ID携带方式有两种，一种是通过此标识符单个携带如 ACT=controles&actionID=1001，另一种是通过配置模块名称与模块编号映射表CHECK_ACTION_ARRAY_ID 如array('controles'=>'1001')
    'CHECK_ACTION_ARRAY_ID' => array(), // action 名称与模块编号映射表 形式 array('模块名1'=>'模块编号id', '模块名2'=>'模块编号id') 用于模块权限验证时优先读取此映射表内容，若表内没找到匹配ID则再去获取CHECK_ACTION_ID标识所携带的ID
    'RULES_MODE' => true, // action 权限验证
    'RULES_LIST' => array('admin'), // 指定验证模块名
    'POLICY_RULES' => false, // 操作权限验证开关(包括增,删,改,查等这些权限的控制,具体要视后台权限定义而定) true||false
    'TEMPLATE_SUFFIX' => '.html', // 默认模板文件后缀	
    'AUTO_FILTER' => true, // 默认开启自动输入过滤	
    'NOT_AUTO_FILTER_LIST' => array(), // 不开启自动输入过滤的 action名标识 

    /* 错误设置 */
    'DEBUG_LOG' => false, // 默认不进行日志记录
    'TRACK_LOG' => false, // 默认不写操作跟踪日志
    'ERROR_DSP' => 'Off', // 是否显示出错信息 
    'DEBUG_MODE' => false, // 调试模式默认关闭
    'EMV_MODE' => 'Users', // 运行环境默认Users用户模式  Development开发模式 
    'URL_MODE' => false, // 否 URL形式为(/?URL_ACTION=home&URL_MODULE=home&page=3&id=1) 是 拼装URL形式为(/home/home/page/3/id/1) 需建立htaccess文件于根目录下面
    'ERROR_MESSAGE' => '您浏览的页面暂时发生了错误！请稍后再试～', // 错误显示信息 非调试模式有效
    'ERROR_PAGE' => '', // 错误定向页面 
    'RULES_PAGE' => 'home,home', // 权限不足时定向到home模块下的home.php页面 
    'EXCEPTION_TMPL_FILE' => '', // 错误模板页面
    'LOG_FILE_SIZE' => 1048576, // 日志文件大小限制 k
    'TEMPLATE_CHARSET' => 'utf-8', // 模板编码 
    'OUTPUT_CHARSET' => 'utf-8', // 默认输出编码
    'AUTO_LOAD_PATH' => '@.public.,Db.,PLFrame.,Net.,Util.,Include.,Vendor.'   // __autoLoad 的路径设置 当前项目的Model和Action类会自动加载，无需设置 注意搜索顺序
);
?>