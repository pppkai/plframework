<?php

/**
 +------------------------------------------------------------------------------
 * PLFrame框架 权限验证规则
 +------------------------------------------------------------------------------
 * @category lib
 * @package  PLFrame
 * @author   pengzl<pengzl_gz@163.com>
 * @version  $Id: VerifyRules.class.php 7 2010-08-24 08:10:12Z pengzl_gz@163.com $
 +------------------------------------------------------------------------------
 */
class VerifyRules {

    private static $m_RULES_MODE = null;
    private static $m_RULES_LIST = null;
    private static $m_instance = null;

    private function __construct() {
        self::$m_RULES_MODE = C('RULES_MODE');
        self::$m_RULES_LIST = C('RULES_LIST');
    }

    // 单例
    static public function getInstance() {
        if (!is_object(self::$m_instance)) {
            self::$m_instance = new self();
        }
        return self::$m_instance;
    }

    // 启动验证
    function Run() {
        return self::__verityRules();
    }

    // 模块验证
    static private function __verityRules() {
        $m_module_name_list = S(C('USER_SESSION_ACTION_NAME')) ? explode(C('USER_SESSION_SEP'), S(C('USER_SESSION_ACTION_NAME'))) : array();
        $m_module = R(URL_MODULE);
        $m_action = R(URL_ACTION);
        $m_mod_id = C('CHECK_ACTION_ARRAY_ID');
        $m_moduleid = isset($m_mod_id[$m_action]) ? $m_mod_id[$m_action] : R(C('CHECK_ACTION_ID'));

        if (self::$m_RULES_MODE && $m_moduleid) {
            if (count($m_module_name_list)) {
                return in_array($m_action, $m_module_name_list);
            } else {
                return !in_array($m_module, self::$m_RULES_LIST);
            }
        } elseif (self::$m_RULES_MODE && !$m_moduleid) {
            return !in_array($m_module, self::$m_RULES_LIST);
        } else {
            return true;
        }
    }

    // 模块验证 扩展
    static private function __verityRulesEX() {
        // 取模块权限总值
        $m_module_sumt = intval(S(C('USER_AUTHCODE_MOD_SUMT')));

        // 取操作权限总值
        //$m_operate_sumt = S(C('USER_AUTHCODE_OPT_SUMT'));

        $m_module = R(URL_MODULE);
        $m_action = R(URL_ACTION);
        $m_mod_id = C('CHECK_ACTION_ARRAY_ID');
        $m_moduleid = isset($m_mod_id[$m_action]) ? $m_mod_id[$m_action] : R(C('CHECK_ACTION_ID'));
        if (self::$m_RULES_MODE && $m_moduleid) {
            return self::veri_purview($m_module_sumt, (array) $m_moduleid);
        } elseif (self::$m_RULES_MODE && !$m_moduleid) {
            return !in_array($m_module, self::$m_RULES_LIST);
        } else {
            return true;
        }
    }

    // 计算权限值	 calc_purview(array(id1, id2, id3, id4, ...))
    static function calc_purview($id = Array(), $value = 0, $gen_val = 0) {
        if (empty($id))
            return intval($gen_val) + $value;
        $tmp_val = 0;
        foreach ($id as $i) {
            $tmp_val += pow(2, intval($i));
        }
        $value += $tmp_val;
        return intval($gen_val) + $value;
    }

    // 验证权限 veri_purview(权限值, array(id1, id2)) 验证是否有id1与id2的权限
    static function veri_purview($value = 0, $id = Array()) {
        if (empty($value) || empty($id))
            return false;
        $val_tmp = 0;
        $ret_tmp = 0;

        foreach ($id as $i) {
            $ret_tmp += pow(2, intval($i));
            $val_tmp += $value & pow(2, intval($i));
        }

        return $val_tmp == $ret_tmp ? true : false;
    }

    // 计算权限id列表 get_purview_id(权限值, 最大权限ID)
    static function get_purview_id($value = 0, $max_id = 0) {
        if (empty($value) || empty($max_id))
            return Array();
        $val_ids = Array();
        foreach (range(0, $max_id) as $i) {
            if (self::veri_purview($value, (array) $i))
                $val_ids[] = $i;
        }

        return $val_ids;
    }

}
?>