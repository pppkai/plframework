<?php

/**
 +------------------------------------------------------------------------------
 * 环境配置文件
 +------------------------------------------------------------------------------
 * @category PLFrame
 * @package  Common
 * @author   pengzl <pengzl_gz@163.com>
 * @version  $Id: environment.php 1 2014-09-11 02:12:40Z pengzl $
 +------------------------------------------------------------------------------
 */
if (!defined('PLFRAME_PATH'))
    exit(0);
return array(
    'Users' => array(
        'display_errors_startup' => '',
        'display_errors' => '',
        'error_reporting' => ''
    ),
    'Development' => array(
        'display_errors_startup' => 1,
        'display_errors' => 1,
        'error_reporting' => 8191
    )
);
?>
