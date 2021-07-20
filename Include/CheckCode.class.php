<?php

/**
 +------------------------------------------------------------------------------
 * 生成验证码图片类
 +------------------------------------------------------------------------------
 * @category PLFrame
 * @package  Include
 * @author   pengzl<pengzl_gz@163.com>
 * @version  $Id: CheckCode.class.php 2 2015-11-02 02:28:48Z pengzl $
 +------------------------------------------------------------------------------
 */
class CheckCode {

    private static $mCheckNum = '1Ab3'; //验证码符
    private static $mBgString = '.';    //在图片上生成一些符号背景
    private static $mImgHeight = 20;     //图片高
    private static $mImgWidth = 120;    //图片宽
    private static $mCodeStyle = '';     //字符类型 大于4为字母+数字 4中文 1为纯数字
    private static $mFontTtf = '';     //字体

    function __construct($bg_string = '.', $img_height = 20, $img_width = 120, $code_style = '', $font = '') {
        self::$mBgString = $bg_string;
        self::$mImgHeight = $img_height;
        self::$mImgWidth = $img_width;
        self::$mCodeStyle = $code_style;
        self::$mFontTtf = $font;
    }

    /**
     * @desc 生成验证码符
     */
    public function setCheckNum($length = 4, $style = 1) {
        self::$mCodeStyle = intval($style);
        return self::$mCheckNum = build_verify($length, self::$mCodeStyle);
    }
    
    /**
     * @desc 设置字符串
     */
    public function setString($string = '') {
        if (!empty($string)) self::$mCheckNum = $string;
        return self::$mCheckNum;
    }

    /**
     * @desc 设定验证码字符类型
     */
    public function setCodeStyle($Num = '') {
        self::$mCodeStyle = intval($Num);
        return $this;
    }

    /**
     * @desc 生成验证码图片 $ttf 字号大小 为0则随机 中文10-15随机 英文3-5之间随机
     */
    private static function CreateCheckImg($ttf=0) {
        //生成图片
        $img = imagecreate(self::$mImgWidth, self::$mImgHeight);

        //设定图片混色模式
        imagealphablending($img, true);
        ImageColorAllocate($img, 255, 255, 255);

        //定义需要的黑色
        $black = ImageColorAllocate($img, 50, 51, 52);

        //先成一黑色的矩形把图片包围
        ImageRectangle($img, 1, 1, self::$mImgWidth - 1, self::$mImgHeight - 1, $black);

        //下面该生成背景了，其实就是在图片上生成一些符号,符号自己定义就是 
        foreach (range(1, 50) as $i) {
            $color = imageColorAllocate($img, mt_rand(200, 230), mt_rand(200, 240), mt_rand(200, 255));
            imageString($img, 1, mt_rand(2, self::$mImgWidth - 8), mt_rand(2, self::$mImgHeight - 8), self::$mBgString, $color);
        }

        //为了区别于背景，这里的颜色不超过200，上面的不小于200
        $numlength = mb_strlen(self::$mCheckNum, 'utf-8');
        //$color     = imageColorAllocate($img, mt_rand(0,200), mt_rand(0,200), mt_rand(0,200));

        if (self::$mCodeStyle == 4 && is_file(self::$mFontTtf)) {
            //如果中文则使用该字体显示
            foreach (range(0, $numlength - 1) as $i) {
                $color = imageColorAllocate($img, mt_rand(0, 200), mt_rand(0, 200), mt_rand(0, 200));
                $_tmpttf = empty($ttf) ? mt_rand(10, 15) : intval($ttf);
                $_tmph = max(mt_rand(self::$mImgHeight / 2, self::$mImgHeight / 2 + 10), self::$mImgHeight / 2);

                @imagettftext(
                    $img, $_tmpttf, mt_rand(0, 10), max(($i + 1) * 12, $i * self::$mImgWidth / $numlength + mt_rand(10, 20) - 4),
                    //mt_rand(self::$mImgWidth/$numlength+mt_rand(1,10)-10, self::$mImgWidth/$numlength+mt_rand(1,10)+10),
                    //mt_rand(self::$mImgHeight/2, self::$mImgHeight/2+5), 
                    $_tmph, $color, self::$mFontTtf, msubstr(self::$mCheckNum, $i, 1)
                );
            }
        } else {
            //如果不加载字体，则使用默认字体
            foreach (range(0, $numlength - 1) as $i) {
                $color = imageColorAllocate($img, mt_rand(0, 200), mt_rand(0, 200), mt_rand(0, 200));
                $tmpttf = empty($ttf) ? mt_rand(5, 50) : intval($ttf);
                @imageString($img, $tmpttf, max(2, $i * self::$mImgWidth / $numlength + mt_rand(1, 10) - 2), mt_rand(1, self::$mImgHeight / 2 - 5), substr(self::$mCheckNum, $i, 1), $color);
            }
        }
        return $img;
    }
    
    /**
     * @desc 生成图片
     */
    public static function genImg($str='', $ttf=0, $color=array(), $ttf_file='/conf/verdana.ttf') {
        if (mb_strlen($str, 'utf-8') == 0) return ;
        
        $arr = imagettfbbox($ttf, 0, $ttf_file, $str); 
        $text_width = $arr[2] - $arr[0]; // 字符串文本框长度
        $text_height = $arr[3] - $arr[5]; // 字符串文本框高度

        $im = imagecreate($text_width, $text_height);
        $white = imagecolorallocate($im, 0xFF, 0xFF, 0xFF);
        imagecolortransparent($im, $white); 
        
        $src_color = array(0, 0, 255);
        if (!empty($color) && count($color) == 3) $src_color = $color;        
        $src_col = imagecolorallocate($im, $src_color[0], $src_color[1], $src_color[2]);

        $arr = imagettftext($im, $ttf, 0, 0, $text_height, $src_col, $ttf_file, $str); 
        imageline($im, $arr[0], $arr[1], $arr[2], $arr[3], $src_col);

        return $im;
    }
    
    // 显示验证码
    public static function showCode($type = 'png') {
        if (in_array(strtolower($type), array('png', 'gif', 'jpg'))) {
            if (strtolower($type) == 'jpg') $type = 'jpeg';
            
            ob_get_clean(); // 解决图片无法正常显示BUG
            Header("Content-type: image/{$type}");
            $img = self::CreateCheckImg();
            eval("Image{$type}(\$img);"); //生成png格式
            ImageDestroy($img);
        }
    }
}
?>