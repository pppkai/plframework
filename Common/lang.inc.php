<?php

/**
 +------------------------------------------------------------------------------
 * 语言包
 +------------------------------------------------------------------------------
 * @category PLFrame
 * @package  Common
 * @author   pengzl <pengzl_gz@163.com>
 * @version  $Id: lang.inc.php 4 2014-09-29 08:40:50Z pengzl $
 +------------------------------------------------------------------------------
 */
if (!defined('PLFRAME_PATH'))
    exit(0);
return Array(
    //  核心
    '_PAGE_ERROR' => '页面出错',
    '_MODULE_NOT_EXIST_' => '无法加载模块',
    '_ACTION_NOT_EXIST_' => '无法加载文件或文件不存在',
    '_ERROR_PARSE_' => '非法操作',
    '_LANGUAGE_NOT_LOAD_' => '无法加载语言包',
    '_TEMPLATE_NOT_EXIST_' => '模板不存在',
    '_MODULE_' => '目录',
    '_ACTION_' => '文件',
    '_APP_CONFIG_NOT_EXIST_' => '项目配置文件不存在！',
    '_DOWN_FILE_NOT_EXIST_' => '下载的文件不存在！',
    '_ROUTER_NOT_EXIST_' => '路由不存在或者没有定义',
    '_VALID_ACCESS_' => '没有权限',
    '_ERROR_LOGIN_SID_CR1_' => '登陆受限',
    '_LOGIN_TIME_OUT_' => '登陆超时',    
    
    //  错误提示
    '_WAITING_GOTO_EXIST_' => '请等待系统转向...',
    '_OPERATION_FAIL_' => '操作失败！',
    '_OPERATION_SUCCESS_' => '操作成功！',
    '_INSERT_SUCCESS_' => '新增成功',
    '_INSERT_FAIL_' => '新增失败',
    '_SELECT_NOT_EXIST_' => '要编辑的项目不存在！',
    '_UPDATE_SUCCESS_' => '更新成功',
    '_UPDATE_FAIL_' => '更新失败',
    '_DELETE_SUCCESS_' => '删除成功',
    '_DELETE_FAIL_' => '删除失败',
    '_RECORD_HAS_UPDATE_' => '记录已经更新',
    '_DATA_TYPE_INVALID_' => '非法数据对象！',
    '_OPERATION_WRONG_' => '操作出现错误',
    '_ERROR_PASSWORD_' => '密码错误',
    '_ERROR_USERNAME_' => '用户名错误',
    '_ERROR_INFO_' => '错误信息：',
    '_ERROR_URL_' => '错误页面：',
    '_ERROR_TYPE_' => '错误类型：',
    '_ERROR_TRACE_' => '错误跟踪：',
    '_NOT_LOAD_DB_' => '无法加载数据库',
    '_NOT_SUPPORT_DB_' => '系统暂时不支持数据库',
    '_NO_DB_CONFIG_' => '没有定义数据库配置',
    '_NOT_SUPPERT_' => '不支持',
    '_SUCCESS_LOGOUT_' => '成功退出',
    '_CACHE_TYPE_INVALID_' => '无法加载缓存类型',
    '_CONFIG_FILE_INVALID_' => '无法加载配置文件',
    '_CONFIG_TYPE_INVALID_' => '系统不支持该配置文件类型！',
    '_FILE_NOT_WRITEABLE_' => '目录（文件）不可写',
    '_FILE_NOT_READABLE_' => '目录（文件）不可读',
    '_NO_AUTO_CHARSET_' => '您的系统不支持自动编码转换！',
    '_CLASS_NOT_EXIST_' => '实例化一个不存在的类！',
    '_UNSERIALIZE_CLASS_NOT_EXIST_' => '反序列化的时候缺少类库',
    '_ACCOUNT_NON_VERI_' => '帐号未通过系统审核！',
    '_CHECKCODE_ERROR_' => '验证码出错！',
    
    // 缓存出错提示
    '_CAN_NOT_ADD_CACHE_' => '不能添加缓存 ',
    '_CAN_NOT_DEL_CACHE_' => '不能删除缓存 ',
    
    //  数据库
    '_DB_TYPE_NOT_EXIST_' => '没有指定数据库类型或该类库不存在！',
    '_SQL_SELECT_ERROR_' => 'SQL查询出错',
    '_SQL_ISEMPTY_ERROR_' => 'SQL语句为空',
    '_DB_CONNECT_ERROR_' => '数据库连接失败！',
    
    // 系统名称
    '_SYS_WEB_SITE_' => 'http://www.plframe.com',
    '_SYS_NAME_' => 'PLFrame 后台',
    '_SYS_VER_' => 'v0.7.5',
);
?>