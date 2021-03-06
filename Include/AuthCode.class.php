<?php

/**
 +------------------------------------------------------------------------------
 * 字串加解密处理类
 +------------------------------------------------------------------------------
 * @category PLFrame
 * @package  Include
 * @author   pengzl <pengzl_gz@163.com>
 * @version  $Id: AuthCode.class.php 2 2015-11-02 02:28:48Z pengzl $
 +------------------------------------------------------------------------------
 */
class AuthCode {

    private static $m_key = null;
    private static $m_time_limit = 0; // 密串有效时长 0表示一直有效
    private static $m_instance = null;
    private static $m_crypt_td = null;
    private static $m_crypt_iv = null;
    private static $m_crypt_ks = null;
    

    // 构造函数 
    function __construct($sKey = null, $sTimeLimit = 0) {
        self::$m_key = strlen($sKey) ? $sKey : C('SYS_AUTHCODE_KEY');
        self::$m_time_limit = intval($sTimeLimit) > 0 ? intval($sTimeLimit) : C('SYS_AUTHCODE_TIME_LIMIT');
    }

    function __destruct() {
        self::$m_key = null;
        self::$m_time_limit = 0;
    }

    // 单例
    static public function getInstance() {
        if (!is_object(self::$m_instance)) {
            self::$m_instance = new self();
        }
        return self::$m_instance;
    }

    // 加码
    static function Encrypt($s = null, $sKey = null) {
        strlen($sKey) ? self::$m_key = $sKey : null;
        if (!strlen($s))
            return '';

        $s = self::displacement($s);
        $sTmp = bin2hex($s); 

        // 密文携带时间戳
        $sTmp = time() . $sTmp;  
        
        return base64_encode(urlencode(self::process_key($sTmp, self::$m_key)));        
        //return base64_encode(urlencode(self::_encrypt($sTmp, self::$m_key)));
    }

    // 解码
    static function Decrypt($s = null, $sKey = null, $sTimeLimit = 0) {
        $tm = time();
        strlen($sKey) ? self::$m_key = $sKey : null;
        intval($sTimeLimit) <= 0 ? $sTimeLimit = intval(self::$m_time_limit) : null;

        if (!strlen($s))
            return '';

        $s = self::process_key(urldecode(base64_decode($s)), self::$m_key);
        //$s = self::_decrypt(urldecode(base64_decode($s)), self::$m_key);
        $s_time = substr($s, 0, strlen($tm));
        is_numeric($s_time) ? $s = substr($s, strlen($tm)) : null;        
        $s = pack("H*", $s);

        // 验证密文过期
        if ((intval($sTimeLimit) > 0) && ($tm - intval($s_time) > intval($sTimeLimit))) {
            return null;
        }
        
        return self::undisplacement($s);
    }

    // 字符加密处理
    private static function process_key($s, $sKey) {
        $sKey = md5($sKey);
        $Conts = 0;
        $sTmp = '';
        foreach (range(0, strlen($s) - 1) as $i) {
            $Conts = $Conts == strlen($sKey) ? 0 : $Conts;
            $sTmp .= $s[$i] ^ $sKey[$Conts++];
        }

        return $sTmp;
    }

    // 位移处理
    private static function displacement($s = null) {
        $sTmp = '';
        if ($s) {
            foreach (range(0, strlen($s) - 1) as $i) {
                $sTmp .= chr(ord($s[$i]) >> 1) . (ord($s[$i]) & 1);
            }
        }

        return $sTmp;
    }

    // 反位移
    private static function undisplacement($d = null) {
        $sTmp = '';
        if ($d) {
            for ($i = 0; $i < strlen($d); $i++)
            {
                $st = $d[$i];
                $ii = ++$i;
                $sTmp .= chr((isset($d[$ii]) && ($d[$ii]&1)) ? (ord($st) << 1) + 1 : ord($st) << 1);
            }
        }
        return $sTmp;
    }
    
    // 
    private static function _encrypt($s, $key) {
        self::_mcrypt_init();
        
        $key = substr(md5($key), 0, self::$m_crypt_ks);
        mcrypt_generic_init(self::$m_crypt_td, $key, self::$m_crypt_iv);
        $s = mcrypt_generic(self::$m_crypt_td, $s);        
        mcrypt_generic_deinit(self::$m_crypt_td);
  
        //$s = bin2hex($s); 
        return $s; 
    }
    
    // 
    private static function _decrypt($s, $key) {
        self::_mcrypt_init();
        
        $key = substr(md5($key), 0, self::$m_crypt_ks);        
        mcrypt_generic_init(self::$m_crypt_td, $key, self::$m_crypt_iv);
        
        //$s = pack("H*", $s);
        $s = mdecrypt_generic(self::$m_crypt_td, $s);
        
        mcrypt_generic_deinit(self::$m_crypt_td);
        mcrypt_module_close(self::$m_crypt_td); 
        
        return $s; 
    }
    
    // 
    private static function _mcrypt_init() {        
        if (!is_resource(self::$m_crypt_td)) {
            //self::$m_crypt_td = mcrypt_module_open('rijndael-256', '', 'ofb', '');
            self::$m_crypt_td = mcrypt_module_open(MCRYPT_DES, '', MCRYPT_MODE_ECB, '/usr/lib/mcrypt-modes');
            self::$m_crypt_iv = mcrypt_create_iv(mcrypt_enc_get_iv_size(self::$m_crypt_td), MCRYPT_DEV_RANDOM);
            self::$m_crypt_ks = mcrypt_enc_get_key_size(self::$m_crypt_td); 
        }
    }
}

?>