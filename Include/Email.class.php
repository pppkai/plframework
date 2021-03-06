<?php

/**
 +------------------------------------------------------------------------------
 * PLFrame框架 邮件发送类
 +------------------------------------------------------------------------------
 * @category PLFrame
 * @package  Include
 * @author   pengzl<pengzl_gz@163.com>
 * @version  $Id: Email.class.php 2 2015-11-02 02:28:48Z pengzl $
 +------------------------------------------------------------------------------
 */
class Email {

    // 类对象
    private static $Email = null;

    /**
     * @desc 构造函数
     */
    function __construct() {
        // 加载第三方类文件
        require_once(PLFRAME_PATH . '/Vendor/PHPMailer_v5.1/class.phpmailer.php');

        if (!is_object(self::$Email)) {
            self::$Email = new PHPMailer();
        }
    }

    /**
     *  @desc set the key/value for email class 
     */
    private static function setValue($vals = array()) {        
        foreach ($vals as $key => $val) {
            if (!is_array($val)) {
                self::$Email->$key = $val;
            }
        }
    }

    /**
     * Adds a "Cc" address.
     * @param string $address
     */
    private static function AddCCAddress($address) {         
        !is_array($address) ? $address = array($address) : '';
        foreach ($address AS $add) {
            self::$Email->AddCC($add);
        }
    }

    /**
     *  @desc send a email 
     */
    static function send($targets, $values = array(), $ccaddress = array()) {         
        !is_array($targets) ? $targets = array($targets) : '';

        if (!empty($values))
            self::setValue($values);
        if (!empty($ccaddress))
            self::AddCCAddress($ccaddress);

        foreach ($targets AS $target) {
            self::$Email->AddAddress($target);
        }

        return self::$Email->Send();
    }
    
    /**
     *  @desc clear send addresses 
     */
    function clearAddress() {
        self::$Email->ClearAddresses();
        return $this;
    }
    
    /**
     *  @desc callback by send action 
     */
    function callsend($isSent, $to, $cc, $bcc, $subject, $body) {
        //
        return false;
    }
}
?>