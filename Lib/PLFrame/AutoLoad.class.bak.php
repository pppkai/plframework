<?php

/**
  +------------------------------------------------------------------------------
 * PLFrame框架 按类名自动类加载程序
  +------------------------------------------------------------------------------
 * @category lib
 * @package  PLFrame
 * @author   pengzl<pengzl_gz@163.com>
 * @version  $Id: AutoLoad.class.bak.php 7 2010-08-24 08:10:12Z pengzl_gz@163.com $
  +------------------------------------------------------------------------------
 */
class AutoLoad {
    /* 单例对象 */

    static $instance = null;

    /* 类搜索路径列表 */
    static $includePaths = array();

    /* 类文件扩展名列表 */
    static $extensionNames = array();

    private function __construct() {
        /* 默认搜索路径 */
        self::$includePaths = explode(',', C('AUTO_LOAD_PATH'));

        /* 默认扩展名列表 */
        array_push(self::$extensionNames, '.class.php');
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
        $_includepaths = self::$includePaths;
        $_extensionNames = self::$extensionNames;

        foreach ((array) $_includepaths as $path) {
            foreach ($_extensionNames as $ext) {
                if (import($path . $classname, '', $ext)) {
                    // 如果加载类成功则返回
                    return true;
                }
            }
        }

        if (!class_exists($classname, false) && !interface_exists($classname, false)) {
            throw_exception("类{$classname}加载失败," . implode("加载路径:", $_includepaths));
        }
    }

}

?>