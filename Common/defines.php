<?php

/**
 +------------------------------------------------------------------------------
 * 系统定义文件
 +------------------------------------------------------------------------------
 * @category PLFrame
 * @package  Common
 * @author   pengzl<pengzl_gz@163.com>
 * @version  $Id: defines.php 1 2014-09-11 02:12:40Z pengzl $
 +------------------------------------------------------------------------------
 */
if (!defined('PLFRAME_PATH'))
    exit(0);
define('MEMORY_LIMIT_ON', function_exists('memory_get_usage') ? true : false);

// 记录内存初始使用
if (MEMORY_LIMIT_ON)
    $GLOBALS['_startUseMems_'] = memory_get_usage();

if (!defined('URL_MODULE'))
    define('URL_MODULE', 'M');    // URL模块名参数
if (!defined('URL_ACTION'))
    define('URL_ACTION', 'A');   // URL文件名参数

define('DS', DIRECTORY_SEPARATOR); // 
define('PS', PATH_SEPARATOR); // 
define('PHP_SAPI_NAME', php_sapi_name());
define('IS_APACHE', strstr($_SERVER['SERVER_SOFTWARE'], 'Apache') || strstr($_SERVER['SERVER_SOFTWARE'], 'LiteSpeed'));
define('IS_IIS', PHP_SAPI_NAME == 'isapi' ? 1 : 0);
define('IS_CGI', substr(PHP_SAPI_NAME, 0, 3) == 'cgi' ? 1 : 0);
define('IS_WIN', strstr(PHP_OS, 'WIN') ? 1 : 0 );
define('IS_LINUX', strstr(PHP_OS, 'Linux') ? 1 : 0 );
define('IS_FREEBSD', strstr(PHP_OS, 'FreeBSD') ? 1 : 0);
define('NOW', time());
define('PLFRAME_VER', '1.0.0'); // 当前框架版本
// 当前文件名 即入口文件路径
if (!defined('_PHP_FILE_') && IS_CGI) {
    // CGI/FASTCGI模式下
    $_temp = explode('.php', $_SERVER['PHP_SELF']);
    define('_PHP_FILE_', rtrim(str_replace($_SERVER['HTTP_HOST'], '', $_temp[0] . '.php'), DS));
}
defined('_PHP_FILE_') || define('_PHP_FILE_', rtrim($_SERVER['SCRIPT_NAME'], DS));

// 网站根目录
if (!defined('WEBROOT'))
    define('WEBROOT', ((dirname(_PHP_FILE_) == '/' || dirname(_PHP_FILE_) == '\\') ? '' : dirname(_PHP_FILE_)));

// 绝对根目录
if (!defined('ROOT'))
    define('ROOT', dirname(APP_PATH));

//-------------------系统参数配置-----------------------
define('SYS_DATE', date('20y-m-d H:i:s'));             // 服务器端时间
define('CLASS_DIR', APP_PATH . DS . 'ui' . DS . 'module' . DS);          // 项目类路径
define('CODE_DIR', APP_PATH . DS . 'ui' . DS . 'pages' . DS);           // 代码路径
define('TPL_DIR', APP_PATH . DS . 'ui' . DS . 'style' . DS);           // 模板路径
define('UPLOAD_PATH', APP_PATH . DS . 'uploadfile' . DS);    // 文件保存路径
define('LIB_DIR', PLFRAME_PATH . DS . 'Lib' . DS);      // 框架基础类路径
define('SQL_LAYER', 'mysql');                    // 默认DB类型  
define('LOG_PATH', APP_PATH . DS . 'logs' . DS);          // 默认日志路径  
define('CONF_DIR', APP_PATH . DS . 'conf' . DS);         // 项目配置文件
define('URL_DEFAULT_NAME', 'home');           // 系统默认模块名与文件名件
//------------------------------------------------------
//-------------------系统环境配置-----------------------
// smarty配置
define('SMARTY_TEMPLATE_DIR', TPL_DIR);                 // 模板文件路径
define('SMARTY_COMPILE_DIR', APP_PATH . DS . 'tmp');       // 编译文件保存路径
define('SMARTY_CONFIG_DIR', APP_PATH . DS . 'conf');      // 项目配置文件路径
define('SMARTY_CACHE_DIR', APP_PATH . DS . 'cache');     // 缓存目录
define('SMARTY_LEFT_DELIMITER', '{#');              // 解释左标识
define('SMARTY_RIGHT_DELIMITER', '#}');            // 解释右标识
define('SMARTY_CACHING', false);                  // 是否启用缓存,true启用,false关闭

define('DATA_TYPE_OBJ', 1);
define('DATA_TYPE_ARRAY', 0);
//------------------------------------------------------
// 调试和Log设置
define('WEB_LOG_ERROR', 0);
define('WEB_LOG_DEBUG', 1);
define('SQL_LOG_DEBUG', 2);
define('FILE_LOG', 3);

// 定义模板内常用系統变量名
define('VAR_APP_PATH', 'app_path'); // 项目路径
define('VAR_APP_NAME', 'app_name'); // 项目名称
define('VAR_APP_VERION', 'app_ver'); // 项目版本
define('VAR_APP_ROOT', 'app_root'); // 项目根目录
define('VAR_WEB_ROOT', 'web_root'); // 站点根目录
define('VAR_ACTION_NAME', 'action_name'); // 项目action名
define('VAR_MUDULE_NAME', 'module_name'); // 项目module名
define('VAR_CURR_USER_NAME', 'curr_user_name'); // 当前用户名

// 生成默认home.php
define('DIR_SECURE_FILENAME', URL_DEFAULT_NAME . ',' . UCfirst(URL_DEFAULT_NAME) . ',' . URL_DEFAULT_NAME);
define('APP_CONFIG_CONTENT', '<?php
/**
 +------------------------------------------------------------------------------
 * 自定义项目配置文件 
 +------------------------------------------------------------------------------
 * @category ' . APP_NAME . '   
 * @package  conf
 * @author   pengzl <pengzl_gz@163.com>
 * @version  $Id: defines.php 1 2014-09-11 02:12:40Z pengzl $
 +------------------------------------------------------------------------------
 */
if (!defined(\'PLFRAME_PATH\')) exit(0);
return array(
	/* DB参数 */
	\'DB_HOST\'=>\'localhost\',  
	\'DB_NAME\'=>\'\',
	\'DB_USER\'=>\'root\',
	\'DB_PWD\'=>\'\',
	\'DB_PORT\'=>\'3306\',
	
	\'USER_SESSION_KEY\'=>\'_PLFRAME_ID_\',	 // 用户ID标识
	\'TEMPLATE_SUFFIX\'=>\'.html\',         // 默认模板文件后缀
	\'AUTO_FILTER\'=>true,                 // 是否开启自动输入过滤 true||false
	\'NOT_AUTO_FILTER_LIST\'=>array(\'\'),  // 不开启自动输入过滤的 action名标识
	\'LOGIN_AUTO_CHECK\'=>true,            // 是否开启页面登陆验证 true||false
	\'LOGIN_CHECK_ARR\'=>\'admin|*\',     // 当AUTO_CHECK设为true时此配置方被启用 作用于SESSION验证页面配置 数组格式 module名称|action名称 module名称|* 表示此模块下所有页面均需做验证  例:注册页面 user|register 当然一般注册页面不能设为自动验证
	\'RULES_MODE\'=>true,                // action 权限验证 true||false
	\'CHECK_ACTION_ARRAY_ID\'=>array(),   // action 名称与模块编号映射表
	\'RULES_LIST\'=>array(\'admin\'),    // 指定需验证module模块名    
	
	\'EMV_MODE\'=>\'Users\',	        // 运行环境默认 Users 用户模式  Development 开发模式 
	\'TRACK_LOG\'=>false,        // 写操作跟踪日志 true||false
	\'DEBUG_LOG\'=>true,        // 写出错日志 true||false
    \'ERROR_DSP\'=>Off,    // 是否显示出错信息 On||Off
    \'DEBUG_MODE\'=>false     // 开启调式模式 true||false
);
?>
');

define('HOME_PHP_CONTENT', '<?php
/**
 +------------------------------------------------------------------------------
 * 测试安装程序 
 +------------------------------------------------------------------------------
 * @category ' . APP_NAME . '
 * @package  ui/pages
 * @author   pengzl <pengzl_gz@163.com>
 * @version  $Id: defines.php 1 2014-09-11 02:12:40Z pengzl $
 +------------------------------------------------------------------------------
 */
//import(\'@.\' . G(URL_MODULE) . \'.\' . ucfirst(URL_DEFAULT_NAME));  // 显示加载类文件
//$Home = get_instance(\'Home\'); // 隐式加载类文件
$Home = new Home; // 或直接new 对象都可以

$Home->set(\'appName\', APP_NAME);  
$Home->set(\'appPath\', APP_PATH);  
$Home->set(\'appCodeDir\', CODE_DIR);  
$Home->set(\'appClassDir\', CLASS_DIR); 
$Home->set(\'appTplDir\', TPL_DIR); 
$Home->view();  //加载模板
?>
');

define('HOME_CLASS_CONTENT', '
<?php
/**
 +------------------------------------------------------------------------------
 * 自定义页面操作类程序 
 +------------------------------------------------------------------------------
 * @category ' . APP_NAME . '
 * @package  ui/module
 * @author   pengzl <pengzl_gz@163.com>
 * @version  $Id: defines.php 1 2014-09-11 02:12:40Z pengzl $
 +------------------------------------------------------------------------------
 */
class Home extends View
{
    /**
    * @brief 构造函数
    * 
    * 默认构造函数
    */	
    function __construct()
    {
        parent::__construct();
    }
}
?>
');

define('HOME_HTML_CONTENT', '<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title> 测试页面 ---- 框架配置成功！</title>
<meta http-equiv="content-type" content="text/html;charset=utf-8"/>
<meta name="pengzl" content="UltralEdit" />
<style>
    body {font-family:Verdana; font-size:14px;}
    h2 {border-bottom:1px solid #DDD;padding:8px 0;font-size:25px;text-align:center;}
    h1 {border-bottom:1px solid #DDD;padding:8px 0;font-size:15px;text-align:center;}
    .title {margin:4px 0;color:#F60;font-weight:bold;}
    .message {padding:1em;border:solid 1px #000;margin:10px 0;background:#FFD;line-height:100%;background:#FFD;color:#2E2E2E;border:1px solid #E0E0E0;}
    .notice {padding:10px;margin:5px;color:#666;background:#FCFCFC;border:1px solid #E0E0E0;}
</style>
</head>
<body>
    <div class="notice">
        <h2> 测试信息===框架配置成功! </h2>
        <p class="title">[ 当前项目名称 ]</p>
        <p class="message">{# $appName #}</p>
        <p class="title">[ 当前项目路径 ]</p>
        <p class="message">{# $appPath #}</p>	
        <p class="title">[ 当前逻辑文件目录 ]</p>
        <p class="message">{# $appCodeDir #}</p>	
        <p class="title">[ 当前项目类文件目录 ]</p>
        <p class="message">{# $appClassDir #}</p>	
        <p class="title">[ 当前项目模板文件目录 ]</p>
        <p class="message">{# $appTplDir #}</p>	
        <h1><a href="http://www.PLFrame.com" target="_blank"> PLFrame&nbsp;v' . PLFRAME_VER . '&#8482; </a></h1>
    </div>
</body>
</html>
');
?>