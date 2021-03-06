<?php

/**
 +------------------------------------------------------------------------------
 * PLFrame框架 初始化文件
 +------------------------------------------------------------------------------
 * @category lib
 * @package  PLFrame
 * @author   pengzl<pengzl_gz@163.com>
 * @version  $Id: App.class.php 17 2011-03-14 01:43:43Z pengzl_gz@163.com $
 +------------------------------------------------------------------------------
 */
class App extends Base {

    protected $m_module; // module 目录名称
    protected $m_action; // action 文件名称

    function __construct() {
        // 加载默认配置文件
        C(require_once(PLFRAME_PATH . '/Common/convention.php'));

        // 加载默认语言文件
        L(require_once(PLFRAME_PATH . '/Common/lang.inc.php'));

        // 加载项目配置文件
        is_file(CONF_DIR . 'config.inc.php') && C(require_once(CONF_DIR . 'config.inc.php'));

        // 定义常量
        $this->m_module = R(URL_MODULE);
        $this->m_action = R(URL_ACTION);
        defined('MODULE_NAME') || define('MODULE_NAME', $this->m_module);
        defined('ACTION_NAME') || define('ACTION_NAME', $this->m_action);

        // 添加当前模块路径至自动加载路径里面
        C('AUTO_LOAD_PATH', '@.' . MODULE_NAME . '.,' . C('AUTO_LOAD_PATH'));

        // 自动创建数据库   
        boolVal(C('CREATE_DATABASE')) && self::createDatabase();

        // 初始化环境 
        core::getInstance()->Run();
    }

    /**
     * @desc 执行模块
     */
    public function Run() {
        // 系统错误 
        class_exists('PLFException') && method_exists('PLFException', 'autoException') && set_exception_handler(array('PLFException', 'autoException'));

        // 自定义出错定制 分两种 开发模式/用户模式
        ('Development' === C('EMV_MODE')) && boolVal(C('ERROR_DSP')) && class_exists('PLFException') && function_exists('errorExceptionHandler') && set_error_handler('errorExceptionHandler', E_ALL);
        ('Users' === C('EMV_MODE')) && boolVal(C('ERROR_DSP')) && class_exists('PLFException') && method_exists('PLFException', 'autoErrorTrigger') && set_error_handler(array('PLFException', 'autoErrorTrigger'), E_ALL);

        // 保存页面来路 SESSION
        S('_REFERER_', isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '');

        // url简单解析
        boolVal(C('URL_MODE')) && self::doUrlAnalyze(G('urlpath'), $_SERVER['REQUEST_METHOD']);

        // 自动过滤  
        boolVal(C('AUTO_FILTER')) && self::doFilter();

        // 注册类自动加载模块
        AutoLoad::getInstance()->registerAutoLoad();

        // 登陆验证
        boolVal(C('LOGIN_AUTO_CHECK')) && self::checkLogin();

        // 权限验证
        self::checkVerifyRules();
        
        // 加载逻辑文件
        self::loadFile();
    }

    /**
     * @desc 载入逻辑文件
     */
    public static function loadFile($A = ACTION_NAME, $M = MODULE_NAME) {
        if (!is_file(CODE_DIR . "{$M}/{$A}.php")) {
            throw_exception(L('_ACTION_NOT_EXIST_'));
        } else {
            require CODE_DIR . "{$M}/{$A}.php";
        }
    }

    /**
     * @desc 登陆验证
     */
    public static function checkLogin() {
        if (self::isCheckSession(C('LOGIN_CHECK_ARR'), R(URL_MODULE), R(URL_ACTION)) && !self::isLoggedin()) {
            $urls = boolVal(C('URL_MODE')) ? '/user/login' : WEBROOT . '/?' . URL_MODULE . '=user&' . URL_ACTION . '=login';
            if (isAjax()) exit(json_encode(array('s'=>0, 'm'=>L('_LOGIN_TIME_OUT_'), 'u'=>$urls)));
            gourltop($urls);
        }
    }

    /**
     * @desc 权限验证
     */
    public static function checkVerifyRules() {
        list($m_mod, $m_act) = explode(',', C('RULES_PAGE'));
        if (!VerifyRules::getInstance()->Run()) {            
            $urls = boolVal(C('URL_MODE')) ? "/{$m_mod}/{$m_act}" : WEBROOT . '/?' . URL_MODULE . "={$m_mod}&" . URL_ACTION . "={$m_act}";
            if (isAjax()) exit(json_encode(array('s'=>0, 'm'=>L('_VALID_ACCESS_'), 'u'=>$urls)));            
            gourltop($urls, L('_VALID_ACCESS_'));
        }
    }

    /**
      +----------------------------------------------------------
     * @desc 是否需要做登陆验证
      +----------------------------------------------------------
     * @param array  $CheckArr 验证数组
     * @param string $ModuleId 模块名
     * @param string $ActionId 文件名
      +----------------------------------------------------------
     * @return Boolean
      +----------------------------------------------------------
     */
    public static function isCheckSession($CheckArr, $Module = '*', $Action = '*') {
        $_intersect = array();
        $_check_arr = is_array($CheckArr) ? $CheckArr : explode(',', $CheckArr);
        array_push($_intersect, $Module . '|' . $Action, $Module . '|*', '*|' . $Action);

        if (array_intersect($_intersect, $_check_arr))
            return true;

        return false;
    }

    /**
     * @desc 是否登陆
     */
    public static function isLoggedin() {
        return S(C('USER_SESSION_KEY')) ? true : false;
    }

    /**
     * @desc 框架过滤
     */
    protected function doFilter() {
        if (!in_array(ACTION_NAME, C('NOT_AUTO_FILTER_LIST'))) {
            if (count($_GET)) {
                autoFilter($_GET);
            }

            if (count($_POST)) {
                autoFilter($_POST);
            }

            if (count($_REQUEST)) {
                autoFilter($_REQUEST);
            }
        }
    }

    /**
     * @desc 非传统路由解析
     */
    protected function doUrlAnalyze($urlStr = 'home/home', $method = 'GET') {
        $arr_urls = explode('/', $urlStr);
        $arr_url = array_slice($arr_urls, 2);

        /*
          @eval('$_'.$method.'[\'' . URL_MODULE . '\'] = strtolower(' . $arr_urls[0] . ');');
          @eval('$_'.$method.'[\'' . URL_ACTION . '\'] = strtolower(' . $arr_urls[1] . ');');
         */
        @eval('$_' . $method . '[\'' . URL_MODULE . '\'] = ' . $arr_urls[0] . ';');
        @eval('$_' . $method . '[\'' . URL_ACTION . '\'] = ' . $arr_urls[1] . ';');

        foreach ($arr_url as $key => $value) {
            if ($key % 2 === 0) {
                @eval('$_' . $method . '[\'' . $arr_url[$key] . '\'] = ' . $arr_url[$key + 1] . ';');
            } else {
                continue;
            }
        }
    }

    /**
     * @desc 创建数据库
     */
    protected static function createDatabase() {
        C('CREATE_DATABASE', false);
        $db_name = C('DB_NAME');
        empty($db_name) && C('DB_NAME', 'db_PLFrame');

        $db_con = mysql_connect(C('DB_HOST'), C('DB_USER'), C('DB_PWD'));
        mysql_query('CREATE DATABASE IF NOT EXISTS`' . C('DB_NAME') . '`', $db_con);
        unset($db_con, $db_name);
        return true;
    }

}
?>