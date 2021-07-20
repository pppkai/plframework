<?php

/**
  +------------------------------------------------------------------------------
 * Http 工具类
  +------------------------------------------------------------------------------
 * @category   Lib
 * @package    Net
 * @author     pengzl <pengzl_gz@163.com>
 * @version    $Id: Http.class.php 16 2011-02-21 10:58:23Z pengzl_gz@163.com $
  +------------------------------------------------------------------------------
 */
class Http extends Base {

    // 当前SOCKET对象
    private static $curr_fsocket = array();

    /**
      +----------------------------------------------------------
     * 下载文件 
     * 可以指定下载显示的文件名，并自动发送相应的Header信息
     * 如果指定了content参数，则下载该参数的内容
      +----------------------------------------------------------
     * @static
     * @access public 
      +----------------------------------------------------------
     * @param string $filename 下载文件名
     * @param string $showname 下载显示的文件名
     * @param string $content  下载的内容
     * @param integer $expire  下载内容浏览器缓存时间
      +----------------------------------------------------------
     * @return void
      +----------------------------------------------------------
     * @throws PLFExecption
      +----------------------------------------------------------
     */
    static function download($filename, $showname = '', $content = '', $expire = 180) {
        if (is_file($filename)) {
            $length = filesize($filename);
        } elseif (is_file(UPLOAD_PATH . $filename)) {
            $filename = UPLOAD_PATH . $filename;
            $length = filesize($filename);
        } elseif ($content) {
            $length = strlen($content);
        } else {
            throw_exception($filename . L('_DOWN_FILE_NOT_EXIST_'));
        }

        if (empty($showname))
            $showname = $filename;
        $showname = basename($showname);

        if (!empty($filename)) {
            $type = mime_content_type($filename);
        } else {
            $type = 'application/octet-stream';
        }

        //发送Http Header信息 开始下载
        ob_end_clean();
        Header('Pragma: public');
        Header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $expire) . 'GMT');
        Header('Cache-Control: private');
        Header('Cache-Component: must-revalidate, post-check=0, pre-check=0');
        Header('Last-Modified: ' . gmdate('D, d M Y H:i:s', time()) . 'GMT');
        Header('Content-Disposition: attachment; filename=' . $showname);
        Header('Content-Length: ' . $length);
        Header('Content-type: ' . $type);
        Header('Content-Encoding: none');
        Header('Content-Transfer-Encoding: binary');

        if (empty($content)) {
            readfile($filename);
        } else {
            echo($content);
        }
        exit(0);
    }

    /**
      +----------------------------------------------------------
     * 显示HTTP Header 信息
      +----------------------------------------------------------
     * @return string
      +----------------------------------------------------------
     */
    static function getHeaderInfo($Header = '', $echo = true) {
        ob_start();
        $Headers = getallHeaders();

        if (!empty($Header)) {
            $info = $Headers[$Header];
            echo($Header . ':' . $info . "\n");
        } else {
            foreach ($Headers as $key => $val) {
                echo("$key:$val\n");
            }
        }

        $output = ob_get_clean();

        if ($echo) {
            echo(nl2br($output));
        } else {
            return $output;
        }
    }

    /**
      +----------------------------------------------------------
     * 发送HTTP Header 信息
      +----------------------------------------------------------
     * @param int $num
      +----------------------------------------------------------
     */
    static function sendHttpStatus($code) {
        static $_status = array(
    // Informational 1xx
    100 => 'Continue',
    101 => 'Switching Protocols',
    // Success 2xx
    200 => 'OK',
    201 => 'Created',
    202 => 'Accepted',
    203 => 'Non-Authoritative Information',
    204 => 'No Content',
    205 => 'Reset Content',
    206 => 'Partial Content',
    // Redirection 3xx
    300 => 'Multiple Choices',
    301 => 'Moved Permanently',
    302 => 'Found', // 1.1
    303 => 'See Other',
    304 => 'Not Modified',
    305 => 'Use Proxy',
    // 306 is deprecated but reserved
    307 => 'Temporary Redirect',
    // Client Error 4xx
    400 => 'Bad Request',
    401 => 'Unauthorized',
    402 => 'Payment Required',
    403 => 'Forbidden',
    404 => 'Not Found',
    405 => 'Method Not Allowed',
    406 => 'Not Acceptable',
    407 => 'Proxy Authentication Required',
    408 => 'Request Timeout',
    409 => 'Conflict',
    410 => 'Gone',
    411 => 'Length Required',
    412 => 'Precondition Failed',
    413 => 'Request Entity Too Large',
    414 => 'Request-URI Too Long',
    415 => 'Unsupported Media Type',
    416 => 'Requested Range Not Satisfiable',
    417 => 'Expectation Failed',
    // Server Error 5xx
    500 => 'Internal Server Error',
    501 => 'Not Implemented',
    502 => 'Bad Gateway',
    503 => 'Service Unavailable',
    504 => 'Gateway Timeout',
    505 => 'HTTP Version Not Supported',
    509 => 'Bandwidth Limit Exceeded'
        );

        if (array_key_exists($code, $_status))
            Header('HTTP/1.1 ' . $code . ' ' . $_status[$code]);
    }

    /**
      +----------------------------------------------------------
     * request 头信息拼装
      +----------------------------------------------------------
     * @param string|array $ustr
     * @param string $type
     * @param string $referer
     * @param string|array $data
      +----------------------------------------------------------
     */
    static function requestInfo($ustr, $type, $referer = null, $data = null) {
        if (is_string($ustr)) {
            $ustr = parse_url($url);
            $ustr['path'] = ($ustr['path'] == '' ? '/' : $ustr['path']);
            $ustr['port'] = !$ustr['port'] ? 80 : $ustr['port'];
        }
        $request = $ustr['path'] . (isset($ustr['query']) && $ustr['query'] != '' ? '?' . $ustr['query'] : '') . (isset($ustr['fragment']) && $ustr['fragment'] != '' ? '#' . $ustr['fragment'] : '');
        !empty($referer) && (stripos($referer, 'http://') === false) && ($referer = 'http://' . $referer);

        if (!empty($data)) {
            if (!is_array($data))
                $data = explode('&', $data);

            foreach ($data as $key => $val) {
                $data_arr[] = urlencode($key) . '=' . urlencode($val);
            }

            if (isset($data_arr))
                $data = implode('&', $data_arr);
        }

        switch (strtoupper($type)) {
            case 'GET':
                $http_info[] = "GET {$request} HTTP/1.0\r\n";
                $http_info[] = "Accept: */*\r\n";
                $http_info[] = "User-Agent: Lowell-Agent\r\n";
                $http_info[] = "Host: {$ustr['host']}\r\n";
                !empty($referer) && $http_info[] = "Referer: {$referer}\r\n";
                $http_info[] = "Connection: Close\r\n\r\n";
                break;

            case 'POST':
                $http_info[] = "POST {$request} HTTP/1.0\r\n";
                $http_info[] = "Accept: */*\r\n";
                $http_info[] = "Host: {$ustr['host']}\r\n";
                !empty($referer) && $http_info[] = "Referer: {$referer}\r\n";
                $http_info[] = "User-Agent: Lowell-Agent\r\n";
                $http_info[] = "Content-type: application/x-www-form-urlencoded\r\n";
                $http_info[] = "Content-Length: " . mb_strlen($data, 'utf-8') . "\r\n";
                $http_info[] = "Connection: Close\r\n\r\n";
                $http_info[] = "{$data}\r\n\r\n";
                unset($data);
                break;
        }

        return isset($http_info) ? implode('', $http_info) : '';
    }

    /**
      +----------------------------------------------------------
     * curl 模拟GET/POST
      +----------------------------------------------------------
     * @param string $method
     * @param string $url
     * @param string|array $vars
     * @param string callback    
      +----------------------------------------------------------
     */
    static function CURLRequest($url, $method = 'get', $vars = null, &$callback = null) {
        $ch = curl_init();
        $options = array(
            CURLOPT_URL => $url,
            CURLOPT_HEADER => 0,
            CURLOPT_USERAGENT => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '',
            CURLOPT_FOLLOWLOCATION => 1,
            CURLOPT_RETURNTRANSFER => 1,
            
            // set cookie
            //CURLOPT_CRLF => 1,
            //CURLOPT_COOKIEJAR => '/tmp/cookie.txt',
            //CURLOPT_COOKIEFILE => '/tmp/cookie.txt',            
        );

        if (strtoupper($method) == 'POST') {
            $options[CURLOPT_POST] = 1;
            $options[CURLOPT_POSTFIELDS] = is_array($vars) ? http_build_query($vars) : $vars;
        }
        
        curl_setopt_array($ch, $options);
        $data = curl_exec($ch);        
        curl_close($ch);
        
        if (strlen($data)) {
            // function_exists            
            if ($callback && is_callable($callback))
                return call_user_func($callback, $data);
            
//            if (ord(mb_substr($data, 0, 1, 'utf-8')) > 127) 
//                $data = mb_substr($data, 1, mb_strlen($data), 'utf-8');
            return $data;
        }
        return curl_error($ch);
    }

    /**
      +----------------------------------------------------------
     * socket 模拟GET/POST
      +----------------------------------------------------------
     * @param string $url
     * @param string $type
     * @param string|array $pData
     * @param string $ref
     * @param int $fkTimeout
      +----------------------------------------------------------
     */
    static function SOCKETRequest($url, $type = 'GET', $pData = null, $ref = null, $fkTimeout = 20) {
        $ustr = parse_url($url);
        $ustr['path'] = !isset($ustr['path']) || $ustr['path'] == '' ? '/' : $ustr['path'];
        $ustr['port'] = !isset($ustr['port']) || !$ustr['port'] ? 80 : $ustr['port'];
        $host_ip = isset($ustr['host']) ? gethostbyname($ustr['host']) : '127.0.0.1';

        $fsock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if (!$fsock)
            return socket_strerror(socket_last_error());

        socket_set_nonblock($fsock);
        @socket_connect($fsock, $host_ip, $ustr['port']);

        if (socket_select($fd_read = array($fsock), $fd_write = array($fsock), $except = NULL, $fkTimeout, 0) != 1) {
            socket_close($fsock);
            return 'connect error or timeout';
        }

        $http_info = self::requestInfo($ustr, $type, $ref, $pData);
        if (!@socket_write($fsock, $http_info, mb_strlen($http_info, 'utf-8'))) {
            socket_close($fsock);
            return 'socket writing error';
        }
        unset($http_info);
        socket_set_block($fsock);
        @socket_set_option($fsock, SOL_SOCKET, SO_RCVTIMEO, array('sec' => $fkTimeout, 'usec' => 0));

        // 处理返回信息
        $ret = array();
        while ($buff = socket_read($fsock, 2048)) {
            $ret[] = $buff;
        }

        $ret = implode('', $ret);
        @socket_close($fsock);
        if (!$ret)
            return '';

        return self::parseAnalyze($ret);

//        $pos = strpos($ret, "\r\n\r\n");
//        $head = substr($ret, 0, $pos);    // http head
//        $status = substr($head, 0, strpos($head, "\r\n"));    // http status line
//        $body = substr($ret, $pos + 4);    // page body
//        if (preg_match("/^HTTP\/\d\.\d\s(\d{3,4})\s/", $status, $matches)) return (intval($matches[1]) / 100 == 2) ? $body : '';
//        return false;
    }

    /**
      +----------------------------------------------------------
     * SOCKETConnect
      +----------------------------------------------------------
     * @param string $url
      +----------------------------------------------------------
     */
    static function SOCKETConnect($url, $Data = null, $fkTimeout = 1) {
        $ustr = parse_url($url);
        $ustr['port'] = !$ustr['port'] ? 80 : $ustr['port'];
        $host_ip = isset($ustr['host']) ? gethostbyname($ustr['host']) : '127.0.0.1';
        $fsock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

        if (!$fsock)
            return socket_strerror(socket_last_error());
        @socket_connect($fsock, $host_ip, $ustr['port']);
        if (!@socket_write($fsock, $Data, mb_strlen($Data, 'utf-8'))) {
            socket_close($fsock);
            return 'socket writing error';
        }

        socket_set_block($fsock);
        @socket_set_option($fsock, SOL_SOCKET, SO_RCVTIMEO, array('sec' => $fkTimeout, 'usec' => 0));

        // 处理返回信息
        $ret = array();
        while ($buff = socket_read($fsock, 8192)) {
            $ret[] = $buff;
        }

        $ret = implode('', $ret);
        @socket_close($fsock);
        return $ret;
    }

    /**
      +----------------------------------------------------------
     * url安全编码
      +----------------------------------------------------------
     * @param string $url
      +----------------------------------------------------------
     */
    static function safeUrl($url) {
        if (!empty($url)) {
            $ulist = parse_url($url);
            if (is_array($ulist) && isset($ulist['query'])) {
                $vlist = explode('&', $ulist['query']);
                $klist = array();

                foreach ($vlist as $key => $value) {
                    $keyvalue = explode('=', $value);
                    if (count($keyvalue) == 2) {
                        $keyvalue[1] = urlencode($keyvalue[1]);
                        array_push($klist, implode('=', $keyvalue));
                    }
                }

                $ulist['query'] = implode('&', $klist);
                $url = (isset($ulist['scheme']) ? $ulist['scheme'] . '://' : '')
                        . (isset($ulist['user']) ? $ulist['user'] . ':' : '')
                        . (isset($ulist['pass']) ? $ulist['pass'] . '@' : '')
                        . (isset($ulist['host']) ? $ulist['host'] : '')
                        . (isset($ulist['port']) ? ':' . $ulist['port'] : '')
                        . (isset($ulist['path']) ? $ulist['path'] : '')
                        . (isset($ulist['query']) ? '?' . $ulist['query'] : '')
                        . (isset($ulist['fragment']) ? '#' . $ulist['fragment'] : '');
            }
        }

        return $url;
    }

    /*
      +----------------------------------------------------------
     * 解析SOCKET请求返回内容
      +----------------------------------------------------------
     * @param string $ret
      +----------------------------------------------------------
     */

    static function parseAnalyze(&$ret = null) {
        if (empty($ret))
            return;
        $pos = strpos($ret, "\r\n\r\n");
        $head = substr($ret, 0, $pos);    // http head
        $status = substr($head, 0, strpos($head, "\r\n"));    // http status line
        $body = substr($ret, $pos + 4);    // page body        
        if (preg_match("/^HTTP\/\d\.\d\s(\d{3,4})\s/", $status, $matches))
            return (intval($matches[1]) / 100 == 2) ? $body : '';
        return false;
    }

}

?>