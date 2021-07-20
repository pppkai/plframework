<?php

/**
 +------------------------------------------------------------------------------
 * 批量文件上传类
 +------------------------------------------------------------------------------
 * @category PLFrame
 * @package  Include
 * @author   pengzl <pengzl_gz@163.com>
 * @version  $Id: upLoadFile.class.php 2 2015-11-02 02:28:48Z pengzl $
 +------------------------------------------------------------------------------
 */
class upLoadFile {

    static private $files;
    static private $file_name;
    static private $file_type;
    static private $file_size;
    static private $is_check_type = true;

    /* 允许上传的文件类型 */
    static private $valid_type = array(
        'image/jpg',
        'image/jpeg',
        'image/png',
        'image/pjpeg',
        'image/gif',
        'image/bmp',
        'image/x-png',
    );

    /* 允许上传的文件的扩展名 */
    static private $valid_ext = array();

    /** 保存名 */
    static private $save_name;

    /** 保存路径 */
    static private $save_path = 'upload/';
    static private $over_write = 0;

    /** 文件最大字节 */
    static private $max_size = 0;

    /** 文件扩展名 */
    static private $ext;

    /** 错误信息 */
    static private $erro_msg = null;

    /** 返回路径 */
    static private $rtn_path = array();

    /**
     * 构造函数
     * @param $files 文件信息数组    
     * 'name' 上传文件名
     * 'size' 上传文件大小
     * 'type' 上传文件类型
     */
    function __construct($files = null) {
        self::$files = empty($files) ? $_FILES['file'] : $files;
        self::$file_name = $files['name'];
        self::$file_type = $files['type'];
        self::$save_name = $files['name'];
        self::$file_size = $files['size'];
    }

    function run() {
        /** 检查目录是否存在并可写 */
        self::is_write(self::$save_path);

        foreach (self::$file_name as $k => $v) {
            self::$ext = self::get_ext($v);

            if (is_array(self::$save_name)) {
                if (empty(self::$save_name[$k])) {
                    self::$save_name[$k] = $v;
                }
            } else {
                if (!empty(self::$save_name)) {
                    self::$save_name[$k] = self::$save_name . '.' . self::$ext;
                } else {
                    self::$save_name[$k] = $v;
                }
            }

            /** 检查文件格式 */
            if (self::$is_check_type && !self::valid_type(self::$file_type[$k])) {
                self::$erro_msg .= '文件类型不正确!';
                return false;
            }

            /** 检查文件扩展名 */
            if (!self::valid_ext(self::$ext)) {
                self::$erro_msg .= '文件扩展名不正确!';
                return false;
            }

            /** 检查文件是否为正常方式上传文件 */
            if (!is_uploaded_file(self::$files['tmp_name'][$k])) {
                self::$erro_msg .= '非法方式上传!';
                return false;
            }

            $dsc_name = self::$save_path . self::$save_name[$k];

            /** 如果不允许覆盖，检查文件是否已经存在 */
            if (!self::$over_write && is_file($dsc_name)) {
                $dsc_name = self::$save_path . substr(self::$save_name[$k], 0, strrpos(self::$save_name[$k], '.')) . '_' . time() . '.' . self::$ext;
            }

            /** 如果有大小限制，检查文件是否超过限制 */
            if (self::$max_size) {
                if (ceil(self::$file_size[$k] / 1024) > self::$max_size) {
                    self::$erro_msg .= '当前文件大小为[' . self::$file_size[$k] . ']k, 文件必须小于' . self::$max_size . 'K!';
                    return false;
                }
            }

            /** 文件上传 */
            if (!@move_uploaded_file(self::$files['tmp_name'][$k], $dsc_name)) {
                self::$erro_msg .= '未知错误!';
                return false;
            } else {
                @chmod($dsc_name, 0777);
            }

            self::$rtn_path[] = $dsc_name;
        }
        return self::$rtn_path;
    }

    /**
     * 新建目录
     * @access private
     */
    private static function createFolder($path, $mode = 0777) {
        if (empty($path)) {
            $_rtn = false;
        } else {
            if (!is_dir($path)) {
                $_rtn = mkdir($path, $mode, true);
                if (!chmod($path, $mode)) {
                    echo "<script>alert('给目录:{$path}赋权限失败!');</script>";
                }
            } else {
                $_rtn = false;
            }
        }
        return $_rtn;
    }

    /**
     * 路径是否可写
     * @access private
     */
    private static function is_write($path) {
        if (!is_dir($path) && !is_file($path)) {
            if (!self::createFolder($path)) {
                self::$erro_msg .= "创建文件夹{$path}失败!";
                return false;
            }
        }

        /* 检查目录是否可写 */
        if (!@is_writable($path)) {
            self::$erro_msg .= $path . '文件夹不可写!';
            return false;
        }
        return true;
    }

    /**
     * 文件格式检查
     * @access private
     */
    private static function valid_type($extmp) {
        if (empty(self::$valid_type) || in_array($extmp, self::$valid_type)) {
            return true;  // 没有格式限制
        } elseif (!in_array($extmp, self::$valid_type)) {
            return false;
        }
    }

    /**
     * 文件扩展名检查
     * @access private
     */
    private static function valid_ext($extmp) {
        if (empty(self::$valid_ext) || in_array($extmp, self::$valid_ext)) {
            return true;  // 没有扩展名限制
        } elseif (!in_array($extmp, self::$valid_ext)) {
            return false;
        }
    }

    /**
     * 获取文件扩展名
     * access private
     */
    private static function get_ext($filename) {
        return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    }

    /**
     * 设置上传文件的最大字节限制
     * @param $maxsize 文件大小(bytes) 0:表示无限制
     * @access public
     */
    function set_max_size($max_size) {
        self::$max_size = $max_size;
        return $this;
    }

    function set_is_check_type($val) {
        self::$is_check_type = $val;
        return $this;
    }

    /**
     * 设置覆盖模式
     * @param 覆盖模式 1:允许覆盖 0:禁止覆盖
     * @access public
     */
    function set_over_write($over_write) {
        self::$over_write = $over_write;
        return $this;
    }

    /**
     * 设置允许上传的文件格式
     * @param $valid_type 允许上传的文件valid_ext格式数组
     * @access public
     */
    function set_valid_type($valid_type) {
        if (is_array($valid_type)) {
            self::$valid_type = $valid_type;
        } else {
            array_push(self::$valid_type, $valid_type);
        }
        return $this;
    }

    /**
     * 设置允许上传的文件的扩展名
     * @param $valid_ext 允许上传的文件扩展名数组
     * @access public
     */
    function set_valid_ext($valid_ext) {
        if (is_array($valid_ext)) {
            self::$valid_ext = $valid_ext;
        } else {
            array_push(self::$valid_ext, strtolower($valid_ext));
        }
        return $this;
    }

    /**
     * 设置保存路径
     * @param $savepath 文件保存路径：以 "/" 结尾
     * @access public
     */
    function set_save_path($save_path) {
        self::$save_path = $save_path;
        return $this;
    }

    /**
     * 设置文件保存名
     * @save_name 保存名，如果未设置文件名，则跟上传的文件名一样
     * @access public
     */
    function set_save_name($save_name) {
        if (!$save_name) {  // 如果未设置文件名，则跟上传的文件名一样
            self::$save_name = self::$file_name;
        } else {
            self::$save_name = $save_name;
        }
        return $this;
    }

    /**
     * 判断是否为UTF8编码
     * @
     * @access public static
     */
    static function isUtf8($str) {
        return preg_match('%^(?:
                            [\x09\x0A\x0D\x20-\x7E]                 # ASCII
                            | [\xC2-\xDF][\x80-\xBF]                # non-overlong 2-byte
                            |     \xE0[\xA0-\xBF][\x80-\xBF]        # excluding overlongs
                            | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}     # straight 3-byte
                            |     \xED[\x80-\x9F][\x80-\xBF]        # excluding surrogates
                            |     \xF0[\x90-\xBF][\x80-\xBF]{2}     # planes 1-3
                            | [\xF1-\xF3][\x80-\xBF]{3}             # planes 4-15
                            |     \xF4[\x80-\x8F][\x80-\xBF]{2}     # plane 16
                            )*$%xs', $str);
    }

    /**
     * 得到错误信息
     * @access public
     * @return error msg string
     */
    public function showtmsg() {
        if (self::$erro_msg)
            exit('<script>alert(\'' . self::$erro_msg . '\');history.back();</script>');
    }

}

?>
