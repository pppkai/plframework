<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


/**
 * Smarty cn_truncate modifier plugin
 *
 * Type:     modifier<br>
 * Name:     truncate<br>
 * Purpose:  Truncate a string to a certain length if necessary,
 *           optionally splitting in the middle of a word, and
 *           appending the $etc string or inserting $etc into the middle.
 * @link http://smarty.php.net/manual/en/language.modifier.truncate.php
 *          truncate (Smarty online manual)
 * @author   Monte Ohrt <monte at ohrt dot com>
 * @param string
 * @param integer
 * @param string
 * @param boolean
 * @param boolean
 * @return string
 */

function smarty_modifier_cn_truncate($str, $start = 0, $length = 0, $suffix = false, $charset = 'utf-8') {
    if (!empty($str)) $str = strip_tags(nl2br($str)); 
    $length = $length ? $length : mb_strlen($str, $charset);
    if (function_exists('mb_substr') && $start >= 0) {
        $slice = mb_substr($str, $start, $length, $charset);
    } elseif (function_exists('iconv_substr') && $start >= 0) {
        $slice = iconv_substr($str, $start, $length, $charset);
    } else {
        $re['utf-8'] = '/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/';
        $re['gb2312'] = '/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/';
        $re['gbk'] = '/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/';
        $re['big5'] = '/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/';
        preg_match_all($re[$charset], $str, $match);        
        $slice = implode('', array_slice($match[0], $start, $length));
    }
    return $suffix ? $slice . 'бнбн' : $slice;
}

/* vim: set expandtab: */

?>
