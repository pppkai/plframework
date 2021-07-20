<?php

/**
  +------------------------------------------------------------------------------
 * PLFrame框架 日志处理类
  +------------------------------------------------------------------------------
 * @category lib
 * @package  PLFrame
 * @author   pengzl<pengzl_gz@163.com>
 * @version  $Id: Log.class.php 16 2011-02-21 10:58:23Z pengzl_gz@163.com $
 * 调试日志文件     systemOut.log
 * 错误日志文件     systemErr.log
 * 数据库日志文件   systemSql.log
  +------------------------------------------------------------------------------
 */
class Log extends Base {

    static $log = array();

    /**
      +----------------------------------------------------------
     * 记录日志
      +----------------------------------------------------------
     */
    static function record($message, $type = WEB_LOG_ERROR) {
        $now = date('[ y-m-d H:i:s ]');
        self::$log[$type][] = "{$now}{$message}\n";
    }

    /**
      +----------------------------------------------------------
     * 日志保存
      +----------------------------------------------------------
     */
    static function save() {
        $day = date('y_m_d');
        $_type = array(
            WEB_LOG_DEBUG => realpath(LOG_PATH) . '/' . $day . '_systemOut.log',
            SQL_LOG_DEBUG => realpath(LOG_PATH) . '/' . $day . '_systemSql.log',
            WEB_LOG_ERROR => realpath(LOG_PATH) . '/' . $day . '_systemErr.log'
        );

        if (!is_writable(LOG_PATH) || !is_dir(LOG_PATH)) {
            if (!is_dir(LOG_PATH)) mk_dir(LOG_PATH);
            if (!is_writable(LOG_PATH)) return L('_FILE_NOT_WRITEABLE_') . ':' . LOG_PATH;
        }        

        foreach (self::$log as $type => $logs) {
            //检测日志文件大小，超过配置大小则备份日志文件重新生成
            $destination = $_type[$type];
            if (is_file($destination) && floor(C('LOG_FILE_SIZE')) <= filesize($destination)) {
                rename($destination, dirname($destination) . '/' . time() . '-' . basename($destination));
            }
            error_log(implode('', $logs), FILE_LOG, $destination);
        }
        clearstatcache();
        return true;
    }

    /**
      +----------------------------------------------------------
     * 日志直接写入
      +----------------------------------------------------------
     */
    static function write($message, $type = WEB_LOG_ERROR, $file = '') {
        $now = date('[y-m-d H:i:s]');

        switch ($type) {
            case WEB_LOG_DEBUG:
                $logType = '[调试]';
                $destination = $file == '' ? LOG_PATH . date('y_m_d') . '_systemOut.log' : $file;
                break;
            case SQL_LOG_DEBUG:
                // 调试SQL记录
                $logType = '[SQL]';
                $destination = $file == '' ? LOG_PATH . date('y_m_d') . '_systemSql.log' : $file;
                break;
            case WEB_LOG_ERROR:
                $logType = '[错误]';
                $destination = $file == '' ? LOG_PATH . date('y_m_d') . '_systemErr.log' : $file;
                break;
        }

        if (!is_writable(LOG_PATH) || !is_dir(LOG_PATH)) {
            if (!is_dir(LOG_PATH)) mk_dir(LOG_PATH);
            if (!is_writable(LOG_PATH)) return L('_FILE_NOT_WRITEABLE_') . ':' . $destination;
        }

        //检测日志文件大小，超过配置大小则备份日志文件重新生成
        if (is_file($destination) && floor(C('LOG_FILE_SIZE')) <= filesize($destination)) {
            rename($destination, dirname($destination) . '/' . time() . '-' . basename($destination));
        }
        
        try {
            error_log("{$logType}{$now}{$message}\n", FILE_LOG, $destination); 
            clearstatcache();
        } catch (Exception $e) {
            //print $e->getMessage();
            return L('_FILE_NOT_WRITEABLE_') . ':' . $e->getMessage();
        }
        
        return true;
    }

}
?>