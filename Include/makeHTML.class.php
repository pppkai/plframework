<?php

/**
 +------------------------------------------------------------------------------
 * 静态页面生成及模板解析类
 +------------------------------------------------------------------------------
 * @category PLFrame
 * @package  Include
 * @author   pengzl <pengzl_gz@163.com>
 * @version  $Id: makeHTML.class.php 2 2015-11-02 02:28:48Z pengzl $
 +------------------------------------------------------------------------------
 */
class makeHTML {

    /**
     * @var data stored here
     */
    private static $_vars = array();

    /**
     * @desc construtor
     *
     */
    private function __construct() {
        
    }

    /**
     * @desc get file content
     *
     * @param string $filename
     * @param bool $isCache
     * @return string
     */
    public static function getFileContent($filename, $isCache = false) {
        static $toName = array();
        $sid = to_guid_string($filename);

        if (isset($toName[$sid]) && $isCache) {
            return $toName[$sid];
        } else if ($filename && stripos($filename, 'http://') === false) {
            $toName[$sid] = is_file($filename) ? (is_readable($filename) ? file_get_contents($filename) : throw_exception(L('_FILE_NOT_READABLE_'))) : '';
        } else if ($filename) {
            $toName[$sid] = file_get_contents($filename);
        }
        return isset($toName[$sid]) ? $toName[$sid] : '';
    }

    /**
     * @desc make file content
     *
     * @param string $filename
     * @param string $content
     * @param string $strip
     * @return bool
     */
    public static function makeFileContent($filename, $content = '', $strip = true) {
        $m_dir = dirname($filename);
        if (!$filename)
            return false;
        if (is_file($filename))
            @unlink($filename);
        if (!is_dir($m_dir) && !mk_dir($m_dir)) {
            throw_exception("创建目录{$m_dir}失败!");
            return false;
        }

        $content = boolVal($strip) && true ? stripslashes($content) : $content;
        if (false === file_put_contents($filename, $content, LOCK_EX))
            return false;
        return true;
    }

    /**
     * @desc set variables - key/value pair
     */
    public static function set($key, $value = null) {
        if (is_array($key)) {
            self::$_vars = array_merge(self::$_vars, $key);
        } else {
            self::$_vars[$key] = $value;
        }
    }

    /**
     * @desc generate static page
     *
     * @param string $filename - 目标文件名(包含路径)
     * @param string $tplfile  - 模版文件名(包含路径)
     */
    static function generate($filename, $tplfile, $clear = true) {
        if (!is_file($tplfile) || empty($filename)) {
            throw_exception(L('_TEMPLATE_NOT_EXIST_'));
            return false;
        }

        extract(self::$_vars, EXTR_SKIP);
        ob_start();
        require $tplfile;
        $_content = ob_get_clean();

        // start make page contents
        if (!self::makeFileContent($filename, $_content)) {
            throw_exception("生成文件:{$filename}失败!");
            return false;
        }

        if ($clear) {
            self::clear();
        }
        return true;
    }

    /**
     * @desc analyse page
     * 
     * @param string $tpl - 模板文件名(默认为当前ACTION名)
     * @param string $clear - 是否清除当前变量
     */
    static function view($tpl = ACTION_NAME, $clear = true, $return = false) {        
        $tpl = str_replace(':', '/', $tpl);
        $tpl = (stripos($tpl, '/') === false) ? MODULE_NAME . '/' . $tpl : $tpl;

        // 推变量进当前链表
        extract(self::$_vars, EXTR_SKIP);
        
        $tpl_file = $tpl;
        if (is_file(TPL_DIR . $tpl . C('TEMPLATE_SUFFIX'))) {
            $tpl_file = TPL_DIR . $tpl . C('TEMPLATE_SUFFIX');
        }
        if (!is_file($tpl_file)) {
            //throw_exception(L('_TEMPLATE_NOT_EXIST_'));
            return false;
        }
        
        ob_start();
        require $tpl_file;
        $clear ? self::clear() : '';        
        if ($return && true) return ob_get_clean();

        $con = OBOutBuffer();        
        echo $con;
        return true;
    }

    /**
     * @desc clear $_vars
     */
    private static function clear() {
        self::$_vars = array();
    }

}