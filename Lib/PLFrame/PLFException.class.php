<?php

/**
 +------------------------------------------------------------------------------
 * PLFrame框架 异常处理类
 +------------------------------------------------------------------------------
 * @category lib
 * @package  PLFrame
 * @author   pengzl<pengzl_gz@163.com>
 * @version  $Id: PLFException.class.php 17 2011-03-14 01:43:43Z pengzl_gz@163.com $
 +------------------------------------------------------------------------------
 */
class PLFException extends Exception {

    private $type;
    
    // Sql调试信息
    public static $_SqlStr = array();

    /**
      +----------------------------------------------------------
     * 架构函数
      +----------------------------------------------------------
     * @access public 
      +----------------------------------------------------------
      +----------------------------------------------------------
     */
    public function __construct() {
        parent::__construct();
    }

    /**
      +----------------------------------------------------------
     * 系统异常输出 所有异常处理类均通过 __toString 方法输出错误
     * 每次异常都会写入系统日志
     * 该方法可以被子类重载
      +----------------------------------------------------------
     * @access public 
      +----------------------------------------------------------
     * @return void
      +----------------------------------------------------------
     */
    public function __toString() {
        $trace = $this->getTrace();

        if (isset($trace[0]))
            $trace = $trace[0];
        isset($trace['class']) && self::setClass($trace['class']);
        isset($trace['function']) && self::setFunction($trace['function']);
        isset($trace['type']) && self::setType($trace['type']);
        
        $tmp_trace = null;
        if (isset($trace['args'][0]) && is_instance_of($trace['args'][0], 'Exception') && $tmp = $trace['args'][0]) {
            $tmp_trace = $tmp->getTrace();
            $tmp->message && self::setMessage($tmp->message);
            empty($tmp->file) || self::setFile($tmp->file);
            empty($tmp->line) || self::setLine($tmp->line);
        }        
        
        $traceInfo = '';
        if (!empty($this->file) && !empty($this->line)) {
            $traceInfo = '[' . date('y-m-d H:i:s') . '] ' . $this->file . ' (' . $this->line . ') ' . "\n";
            $traceInfo .= (!empty($this->class) ? $this->class : '') . (!empty($this->type) ? $this->type : '') . (!empty($this->function) ? $this->function : '') . '(';
            $traceInfo .=!empty($this->class) ? (is_array($this->class) ? implode(',', array_keys($this->class)) : (is_object($this->class) ? implode(',', array_keys(get_object_vars($this->class))) : $this->class)) : '';
            $traceInfo .= ")\n";
        }
        
        if (is_array($tmp_trace)) {
            foreach ($tmp_trace as $t) {
                $traceInfo .= '[' . date('y-m-d H:i:s') . '] ' . (isset($t['file']) ? $t['file'] : '') . ' (' . (isset($t['line']) ? $t['line'] : '') . ') ';
                $traceInfo .= (isset($t['class']) ? $t['class'] : '') . (isset($t['type']) ? $t['type'] : '') . $t['function'] . '(';
                $traceInfo .= isset($t['args']) ? (is_array($t['args']) ? implode(',', array_keys($t['args'])) : (is_object($t['args']) ? implode(',', array_keys(get_object_vars($t['args']))) : $t['args'])) : '';
                $traceInfo .= ")\n";
            }

            $tmp_trace_info = $tmp_trace[0];
            
            if (!empty($tmp_trace[1]) && !empty($tmp_trace[0]['class'])&& !empty($tmp_trace[1]['class']) && (($tmp_trace[0]['class'] == $tmp_trace[1]['class'] && $tmp_trace[0]['class'] == 'db') || $tmp_trace[1]['class'] == 'db')) $tmp_trace_info = $tmp_trace[1];
            empty($tmp_trace_info['file']) || self::setFile($tmp_trace_info['file']);
            empty($tmp_trace_info['line']) || self::setLine($tmp_trace_info['line']);
        }
        
        $counts = explode(':', $this->file);
        if (count($counts) > 1) {
            $tmps = explode('(', $counts[0]);
            $this->file = $tmps[0];
        }
        
        $file = file($this->file);
        $counts = count($file);
        
        $error['message'] = $this->message;
        $error['type'] = $this->type;
        $error['detail'] = "\n" . L('_MODULE_') . ':[' . CODE_DIR . G(URL_MODULE) . ']  ' . "\n" . L('_ACTION_') . ':[' . G(URL_ACTION) . '.php]' . "\n";
        
        if ($this->line - 3 >= 0) $error['detail'] .= 'LINE ' . ($this->line - 2) . ': ' . safeVALUE($file[$this->line - 3]);
        if ($this->line - 2 >= 0) $error['detail'] .= 'LINE ' . ($this->line - 1) . ': ' . safeVALUE($file[$this->line - 2]);
        if ($this->line - 1 >= 0) $error['detail'] .= '<font color="#FF6600" >LINE ' . ($this->line) . ': <b>' . safeVALUE($file[$this->line - 1]) . '</b></font>';
        if ($this->line + 1 <= $counts) $error['detail'] .= 'LINE ' . ($this->line + 1) . ': ' . safeVALUE($file[$this->line]);
        if ($this->line + 2 <= $counts) $error['detail'] .= 'LINE ' . ($this->line + 2) . ': ' . safeVALUE($file[$this->line + 1]);
        
        $error['class'] = $this->class;
        $error['function'] = $this->function;
        $error['file'] = $this->file;
        $error['line'] = $this->line;
        $error['trace'] = $traceInfo;
        $error['sqlstr'] = implode('', self::$_SqlStr);

        // 直接写系统日志
        if (C('DEBUG_LOG')) {
            $errorStr = "\n" . L('_ERROR_INFO_') . '[ ' . CLASS_DIR . G('module') . '/' . G('action') . '.php ]' . $this->message;
            $errorStr .= L('_ERROR_URL_') . $error['file'] . "\n";
            $errorStr .= L('_ERROR_TYPE_') . $this->type . "\n";
            $errorStr .= L('_ERROR_TRACE_') . $traceInfo;
            $file_stat = Log::Write($errorStr);            
            if ($file_stat !== true && $file_stat) {
                $error['message'] = $file_stat;
            }
        }
        
        $e = $error;
        require PLFRAME_PATH . '/Tpl/OutPutERMSG.tpl.php';
        exit(0);
    }

    /**
      +----------------------------------------------------------
     * 自定义用户出错提示处理
      +----------------------------------------------------------
     * @param string $code 出错编码
     * @param string $string 出错信息
     * @param string $file 出错文件
     * @param string $line 出错行数
      +----------------------------------------------------------
     * @return string
      +----------------------------------------------------------
     */
    static function errorTrigger($code, $string, $file, $line) {
        $str = '错误编码: ' . $code . PHP_EOL;
        $str .= '提示信息: ' . $string . PHP_EOL;
        $str_log = '出错文件: ' . $file . PHP_EOL;
        $str_log .= '出错行数: ' . $line . PHP_EOL;

        if (C('DEBUG_LOG'))
            Log::Write(PHP_EOL . $str . $str_log, WEB_LOG_DEBUG);
        
        return $str;
        #return '<pre>' . $str . '</pre>';
    }

    static function autoException() {
        echo new self();        
    }

    static function autoErrorTrigger($code, $string, $file, $line) {
        echo self::errorTrigger($code, $string, $file, $line);
    }

    function setMessage($message) {
        $this->message = $message;
    }

    function setLine($line) {
        $this->line = $line;
    }

    function setFile($file) {
        $this->file = $file;
    }

    function setType($type) {
        $this->type = $type;
    }

    function setFunction($function) {
        $this->function = $function;
    }

    function setClass($class) {
        $this->class = $class;
    }

}
?>