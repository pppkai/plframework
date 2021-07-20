<?php

/**
 +------------------------------------------------------------------------------
 * tripleDES(3DES For JAVA DESede) 
 +------------------------------------------------------------------------------
 * @category    
 * @package  
 * @author   pengzl<pengzl_gz@163.com>
 * @version  $Id: tripleDES.class.php 23 2015-11-12 08:05:51Z pengzl $
 +------------------------------------------------------------------------------
 */
class tripleDES {    

    public static function genIvParameter() {
        return mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_TRIPLEDES, MCRYPT_MODE_CBC), MCRYPT_RAND);
    }

    private static function pkcs5Pad($text, $blocksize) {
        $pad = $blocksize - (strlen($text) % $blocksize); // in php, strlen returns the bytes of $text  
        return $text . str_repeat(chr($pad), $pad);
    }

    private static function pkcs5Unpad($text) {
        $pad = ord($text{strlen($text) - 1});
        if ($pad > strlen($text))
            return false;
        if (strspn($text, chr($pad), strlen($text) - $pad) != $pad)
            return false;
        return substr($text, 0, -1 * $pad);
    }

    public static function enc3DES($plain_text, $key, $iv) {
        $td = mcrypt_module_open(MCRYPT_3DES, '', MCRYPT_MODE_ECB, '');
        $padded = self::pkcs5Pad($plain_text, mcrypt_get_block_size(MCRYPT_3DES, MCRYPT_MODE_ECB));
        mcrypt_generic_init($td, $key, $iv);
        return strtoupper(bin2hex(mcrypt_generic($td, $padded)));
    }

    public static function dec3DES($cipher_text, $key, $iv) {
        $td = mcrypt_module_open(MCRYPT_3DES, '', MCRYPT_MODE_ECB, '');
        mcrypt_generic_init($td, $key, $iv);
        return self::pkcs5Unpad(mdecrypt_generic($td, hex2bin($cipher_text)));
    }

    public static function encryptText($plain_text, $key, $iv) {
        $padded = self::pkcs5Pad($plain_text, mcrypt_get_block_size(MCRYPT_TRIPLEDES, MCRYPT_MODE_CBC));
        return mcrypt_encrypt(MCRYPT_TRIPLEDES, $key, $padded, MCRYPT_MODE_CBC, $iv);
    }

    public static function decryptText($cipher_text, $key, $iv) {
        $plain_text = mcrypt_decrypt(MCRYPT_TRIPLEDES, $key, $cipher_text, MCRYPT_MODE_CBC, $iv);
        return self::pkcs5Unpad($plain_text);
    }

}
