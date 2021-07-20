<?php

/**
  +------------------------------------------------------------------------------
 * PLFrame框架 按类名自动类加载程序
  +------------------------------------------------------------------------------
 * @category lib
 * @package  PLFrame
 * @author   pengzl<pengzl_gz@163.com>
 * @version  $Id: AutoLoad.class.php 16 2011-02-21 10:58:23Z pengzl_gz@163.com $
  +------------------------------------------------------------------------------
 */
class AutoLoad {
    /* 单例对象 */

    private static $instance = null;

    /* 类搜索路径列表 */
    private static $includePaths = array();

    /* 类文件扩展名 */
    private static $extensionNames = '.class.php';

    private function __construct() {
        /* 默认搜索路径 */
        self::$includePaths = explode(',', C('AUTO_LOAD_PATH'));
    }

    /**
     * @desc 通过spl注册自动加载类
     *
     */
    public function registerAutoLoad() {
        ini_set('unserialize_callback_func', 'spl_autoload_call');

        /* 自动加载函数注册 */
        if (method_exists(self::$instance, 'loadClass')) {
            if (!spl_autoload_register(array(self::$instance, 'loadClass'))) {
                throw_exception(sprintf('Unable to register %s::loadClass as an autoloading method.', get_class(self::$instance)));
            }
        }
    }

    /**
     * @desc 获取单例实例
     */
    public static function getInstance() {
        if (!is_object(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 取消spl注册自动加载类
     *
     */
    public function unregisterAutoLoad() {
        spl_autoload_unregister(array(self::$instance, 'loadClass'));
    }

    /**
     * @desc 根据类名自动加载类
     */
    public function loadClass($classname) {
        foreach ((array) self::$includePaths as $path) {
            if (import($path . $classname, '', self::$extensionNames))
                return true;
        }

        if (!class_exists($classname, false) && !interface_exists($classname, false)) {
            throw_exception("类{$classname}加载失败," . implode('加载路径:', self::$includePaths));
        }
    }

}

?>