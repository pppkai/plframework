<?php

/**
 +------------------------------------------------------------------------------
 * PLFrame公共文件
 +------------------------------------------------------------------------------
 * @category PLFrame
 * @package  PLFrame
 * @author   pengzl<pengzl_gz@163.com>
 * @version  $Id: PLFrame.php 16 2011-02-21 10:58:23Z pengzl_gz@163.com $
 +------------------------------------------------------------------------------
 */

defined('APP_NAME') || exit('No defined APP name!');

// 记录开始运行时间
defined('BEGINTIME') || define('BEGINTIME', microtime(TRUE));

// PLFrame系统目录定义
defined('PLFRAME_PATH') || define('PLFRAME_PATH', dirname(__FILE__));
defined('APP_PATH') || define('APP_PATH', dirname(PLFRAME_PATH) . DIRECTORY_SEPARATOR . APP_NAME);

// 加载系统定义文件
require_once PLFRAME_PATH . '/Common/defines.php';

// 加载系统通用函数库
require_once PLFRAME_PATH . '/Common/functions.php';

// 第一次运行检查项目目录结构 如果不存在则自动创建
if (!is_dir(CONF_DIR)) {
    define('CREATE_DIR_SECURE', true);
    createAppDir();
}

// 加载项目自定义文件
is_file(CONF_DIR . 'defines.php') && require_once CONF_DIR . 'defines.php';

// 加载项目扩展函数库
is_file(CONF_DIR . 'functions_extension.php') && require_once CONF_DIR . 'functions_extension.php';

// 系统基类加载开始
define('CLASSLOADTIMEBEG', microtime(TRUE));

// 加载基类
import('PLFrame.Base');
import('PLFrame.Core');

// 加载系统类
import('Smarty.Smarty');
import('PLFrame.PLFException');
import('PLFrame.AutoLoad');
import('PLFrame.App');
import('PLFrame.View');

// 系统基类加载完成
define('CLASSLOADTIMEEND', microtime(TRUE));