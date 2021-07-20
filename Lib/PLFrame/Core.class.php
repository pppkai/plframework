<?php

/**
 +------------------------------------------------------------------------------
 * PLFrame框架 环境初始化文件
 +------------------------------------------------------------------------------
 * @category lib
 * @package  PLFrame
 * @author   pengzl<pengzl_gz@163.com>
 * @version  $Id: Core.class.php 17 2011-03-14 01:43:43Z pengzl_gz@163.com $
 +------------------------------------------------------------------------------
 */
final class Core {

    protected static $m_env_mode;
    protected static $m_env_parse = array();
    protected static $m_instance = null;

    /**
     * @desc 构造函数
     */
    private function __construct() {
        // 环境配置文件
        if (empty(self::$m_env_parse)) {
            // 默认环境配置文件
            self::$m_env_parse = require_once PLFRAME_PATH . '/Common/environment.php';

            // 具体项目环境配置文件
            if (is_file(APP_PATH . '/conf/environment.php')) {
                self::$m_env_parse = array_merge(self::$m_env_parse, require_once APP_PATH . '/conf/environment.php');
            }
        }
    }

    /**
     * @desc 获取单例实例
     */
    public static function getInstance() {
        if (!is_object(self::$m_instance)) {
            self::$m_instance = new self();
        }
        return self::$m_instance;
    }

    /**
     * @desc 启动运行
     */
    public function Run() {
        $this->initEnvironment();
    }

    /**
     * @desc 初始化环境配置
     */
    protected function initEnvironment() {
        set_time_limit(0);

        // 清除变量
        unset(
                $HTTP_GET_VARS, $HTTP_POST_VARS, $HTTP_COOKIE_VARS, $HTTP_SERVER_VARS, $HTTP_ENV_VARS, $_ENV,
                //$_FILES, 
                $GLOBALS
        );

        date_default_timezone_set('Asia/Chongqing');
        ini_set('session.gc_maxlifetime', 3600);
        ini_set('memory_limit', -1); // 取消内存限制

        if (!ini_get('user_agent'))
            ini_set('user_agent', 'PHP');
        if (!isset($_SESSION)) {
            $session_id = R('PHPSESSID');
            if ($session_id) session_id($session_id);
            session_start();
        }
        ob_start();
        Headers_sent() || Header('content-type:text/html;charset=' . C('OUTPUT_CHARSET'));

        // 配置php include路径
        set_include_path(
                PS . LIB_DIR
                . PS . PLFRAME_PATH . '/Vendor'
                . PS . PLFRAME_PATH . '/Include'
                . PS . CLASS_DIR
                . PS . CODE_DIR . MODULE_NAME
                . PS . get_include_path()
                . PS . '..'
        );

        if (isset(self::$m_env_parse[C('EMV_MODE')]) && is_array(self::$m_env_parse[C('EMV_MODE')])) {
            foreach (self::$m_env_parse[C('EMV_MODE')] as $set_name => $set_value) {
                @ini_set($set_name, $set_value);
            }
        }
    }

}

?>