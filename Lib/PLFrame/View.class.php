<?php

/**
  +------------------------------------------------------------------------------
 * PLFrame框架 页面输出处理类
  +------------------------------------------------------------------------------
 * @category lib
 * @package  PLFrame
 * @author   pengzl<pengzl_gz@163.com>
 * @version  $Id: View.class.php 16 2011-02-21 10:58:23Z pengzl_gz@163.com $
  +------------------------------------------------------------------------------
 */
class View extends Smarty {

    function __construct() {
        $this->template_dir = SMARTY_TEMPLATE_DIR;
        $this->compile_dir = SMARTY_COMPILE_DIR;
        $this->config_dir = SMARTY_CONFIG_DIR;
        $this->cache_dir = SMARTY_CACHE_DIR;
        $this->left_delimiter = SMARTY_LEFT_DELIMITER;
        $this->right_delimiter = SMARTY_RIGHT_DELIMITER;
        $this->caching = SMARTY_CACHING;
    }

    // 加载通用常量
    function setDefault($vals = null) {
        $def_vals = array(
            VAR_APP_PATH => APP_PATH,
            VAR_APP_NAME => APP_NAME,            
            VAR_APP_VERION => defined('APP_VER') ? APP_VER : '', 
            VAR_APP_ROOT => ROOT,
            VAR_WEB_ROOT => WEBROOT,
            VAR_ACTION_NAME => URL_ACTION,
            VAR_MUDULE_NAME => URL_MODULE,
            VAR_CURR_USER_NAME => S('__user_name__'),
        );
        $defs = empty($vals) ? $def_vals : $vals;

        $this->set($defs);

        return $this;
    }

    function set($var, $value = null) {
        if (!empty($var))
            $this->assign($var, $value);
        return $this;
    }

    function view($tpl = ACTION_NAME, $status = false) {
        // 加载通用常量
        $this->setDefault();

        $tpl = str_replace(':', '/', $tpl);
        $tpl = (stripos($tpl, '/') === false) ? MODULE_NAME . '/' . $tpl : $tpl;
        is_file(TPL_DIR . $tpl . C('TEMPLATE_SUFFIX')) ? $this->display($tpl . C('TEMPLATE_SUFFIX')) : throw_exception(L('_TEMPLATE_NOT_EXIST_'));

        // 直接显示或返回(用于写静态文件)
        if ($status)
            return OBOutBuffer();

        echo OBOutBuffer();
        return true;
    }

}

?>