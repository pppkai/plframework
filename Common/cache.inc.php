<?php
/**
 +------------------------------------------------------------------------------
 * 缓存配置文件
 +------------------------------------------------------------------------------
 * @category PLFrame
 * @package  Common
 * @author   pengzl<pengzl_gz@163.com>
 * @version  $Id: cache.inc.php 1 2014-09-11 02:12:40Z pengzl $
 +------------------------------------------------------------------------------
 */
if (!defined('PLFRAME_PATH'))
    exit(0);
return array(
    'expire_seconds' => 600,
    'ssn' => array('ssn_server_01' => 'tcp://plframe:plframe@localhost:11211'),
    'desc' => array('ssn_server_01' => 'plframe_server_01')
);
?>