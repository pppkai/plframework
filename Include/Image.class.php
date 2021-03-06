<?php

/**
 +------------------------------------------------------------------------------
 * 图片文件上传处理类
 +------------------------------------------------------------------------------
 * @category PLFrame
 * @package  Include
 * @author   pengzl<pengzl_gz@163.com>
 * @version  $Id: Image.class.php 32 2015-11-24 01:55:59Z pengzl $
 +------------------------------------------------------------------------------
 */
class Image {
    private $image;
    private $imageUrl;
    private $imagepath;
    private $imageSize;
    private $imageWidth;
    private $imageHeight;
    private $image_mime;
    private $saveName;
    private $savePath;
    private $waterPath; // 水印路径
    
    // 水印处理
    private $font_angle = 0; // 倾斜角度
    private $font_text = '文字水印';
    private $font_color = array('233', '14', '91');
    private $font_size = 20;
    private $font_ttf = '';
    private $font_x = 20;
    private $font_y = 20;
    private $img_width;
    private $img_height;
    private $img_url;
    private $img_water;
    private $img_water_x;
    private $img_water_y;
    private $img_water_Transparency = 20; // 水印透明度
    private $img_water_PosType; // 水印位置
    private $check_type = true; // 是否检查类型
    
    // 缩略图设置
    private $shrink_width = 0;
    private $shrink_height = 0;
    private $shrink_type = 'gif';
    
    // 上传设置
    private $upfile_size = 0; // 为0则大小不限制
    private $upfile_type = array(
        'image/jpg',
        'image/jpeg',
        'image/png',
        'image/pjpeg',
        'image/gif',
        'image/bmp',
        'image/x-png'
    );
    private static $image_type_idents = array(
        IMAGETYPE_GIF => array('gif', 'image/gif'), // 1 = GIF
        IMAGETYPE_JPEG => array('jpg', 'image/jpeg,image/jpg,image/pjpeg'), // 2 = JPG
        IMAGETYPE_PNG => array('png', 'image/png'), // 3 = PNG
        IMAGETYPE_SWF => array('swf', 'application/x-shockwave-flash'), // 4 = SWF
        IMAGETYPE_PSD => array('psd', 'image/psd'), // 5 = PSD
        IMAGETYPE_BMP => array('bmp', 'image/bmp'), // 6 = BMP
        IMAGETYPE_TIFF_II => array('tiff', 'image/tiff'), // 7 = TIFF (intel byte order)
        IMAGETYPE_TIFF_MM => array('tiff', 'image/tiff'), // 8 = TIFF (motorola byte order)
        IMAGETYPE_JPC => array('jpc', 'application/octet-stream'), // 9 = JPC 
        IMAGETYPE_JP2 => array('jp2', 'image/jp2'), //10 = JP2
        IMAGETYPE_JPX => array('jpf', 'application/octet-stream'), //11 = JPX
        IMAGETYPE_JB2 => array('jb2', 'application/octet-stream'), //12 = JB2          
        IMAGETYPE_SWC => array('swc', 'application/x-shockwave-flash'), //13 = SWC 
        IMAGETYPE_IFF => array('aiff', 'image/iff'), //14 = IFF
        IMAGETYPE_WBMP => array('wbmp', 'image/vnd.wap.wbmp'), //15 = WBMP
        IMAGETYPE_XBM => array('xbm', 'image/xbm') //16 = XBM
    );
    // 出错提示													   
    private static $erro_no = 0;
    private static $erro_msg = array(
        '',
        '上传文件过大!',
        '类型不符!',
        '移动文件失败!',
        '水印文件读取出错, 路径不对!',
        '文件太小未加水印!',
        '水印文字为空!',
        '字体转换模块iconv未加载!',
        '缩略长度或宽度过小!',
        '创建文件夹失败!',
        '文件夹不可写!',
        '文件不存在或文件过大!',
        '非正确的图片资源!',
        '添加的属性名不存在',
    );

    public function __construct() {
        $this->font_size = 12;
        $this->font_angle = 0;
    }

    //上传
    public function upfile($fname, $savePath = '') {
        clearstatcache();

        if (!isset($_FILES[$fname]) || !$_FILES[$fname]['tmp_name']) {
            self::$erro_no = 11;
            trigger_error('Image: ' . self::$erro_msg[self::$erro_no] . '文件最大不能大于' . ini_get('upload_max_filesize'), E_USER_WARNING);
            return false;
        }

        $file = $_FILES[$fname];
        $fileInfo = pathinfo($file['name']);

        $this->savePath = !empty($savePath) ? $savePath : './' . date('Ymd') . '/';
        if (empty($this->saveName))
            $this->saveName = time() . mt_rand(0, 999999) . '.' . strtolower($fileInfo['extension']);

        // 保存路径是否可写
        if (!self::is_write($savePath))
            return false;

        $savection = $this->savePath . $this->saveName;

        if ($this->upfile_size && ($file['size'] > $this->upfile_size)) {
            self::$erro_no = 1;
            trigger_error('Image: ' . self::$erro_msg[self::$erro_no], E_USER_WARNING);
            return false;
        }

        if ($this->check_type && !in_array($file['type'], $this->upfile_type)) {
            self::$erro_no = 2;
            trigger_error('Image: ' . self::$erro_msg[self::$erro_no], E_USER_WARNING);
            return false;
        }

        if (!@move_uploaded_file($file['tmp_name'], $savection)) {
            self::$erro_no = 3;
            trigger_error('Image: ' . self::$erro_msg[self::$erro_no], E_USER_WARNING);
            return false;
        } else {
            chmod($savection, 0775); // 给文件赋权限
        }

        $temp = getimagesize($savection);
        $this->image = self::readImage($fileInfo['extension'], $savection);
        $this->image_mime = $temp['mime'];
        $this->imageHeight = $temp[0];
        $this->imageWidth = $temp[1];
        $this->imagepath = $savection;
        return $savection;
    }

    //加图片水印
    public function createImg($fname = '', $savePath = '', $raido = 0.3, $maxsize = 560) {
        if (empty($fname))
            $fname = $this->imagepath;
        if ($fileInfo = self::isFileTrue($fname))
            $this->image = self::readImage($fileInfo['extension'], $fname); // 取背景图片信息

        self::isResource($this->image);
        if (empty($this->saveName))
            $this->saveName = $fileInfo['basename'];

        if (empty($savePath)) {
            $savePath = empty($this->savePath) ? $fname : $this->savePath;
        } elseif (!self::is_write($savePath)) {
            return false;
        }

        $savection = $savePath . $this->saveName;

        if (empty($this->img_water))
            return false;

        $bg_w = imagesx($this->image);
        $bg_h = imagesy($this->image);

        // 缩小水印图 当图片过小时    
        if ($bg_w < ($this->img_width + ceil($this->img_width / 2)) || $bg_h < ($this->img_Height + ceil($this->img_Height / 2))) {
            empty($raido) && $raido = 0.3;
            $tmp = self::shotImg($this->img_water, ceil($bg_w * $raido));
            $this->img_width = imagesx($tmp);
            $this->img_Height = imagesy($tmp);
            $this->img_water = self::shrinkRatio($this->img_water, $this->img_width, $this->img_Height);
        }

        imagealphablending($this->image, true);
        imagealphablending($this->img_water, true);

        if (empty($this->img_water_PosType))
            $this->img_water_PosType = 4; // 右下角
        self::WaterPos($this->img_water_PosType);

        imagecopymerge(
            $this->image, $this->img_water, $this->img_water_x, $this->img_water_y, 0, 0, $this->img_width, $this->img_Height, $this->img_water_Transparency
        );

        $this->image = self::shotImg($this->image, $maxsize);
        $path = self::saveImage($this->image, $fileInfo['extension'], $savePath, substr($this->saveName, 0, -(strlen($fileInfo['extension']) + 1)));
        imagedestroy($this->image);
        imagedestroy($this->img_water);

        return $path;
    }

    //建立文字水印
    function createTextImg($fname = '', $savePath = '', $pos = null) {
        if (empty($fname))
            $fname = $this->imagepath;
        if ($fileInfo = self::isFileTrue($fname))
            $this->image = self::readImage($fileInfo['extension'], $fname); //取背景图片信息
        self::isResource($this->image);
        if (empty($this->saveName))
            $this->saveName = $fileInfo['basename'];

        if (empty($savePath)) {
            empty($this->savePath) ? $savePath = $fname : $savePath = $this->savePath;
        } elseif (!self::is_write($savePath)) {
            return false;
        }
        $savection = $savePath . $this->saveName;

        // 指定文字位置
        if (!empty($pos) && count($tmp = explode(',', $pos)) == 2) {
            $this->img_water_x = $tmp[0];
            $this->img_water_y = $tmp[1];
        }

        if (empty($this->font_text)) {
            self::$erro_no = 6;
            trigger_error('Image: ' . self::$erro_msg[self::$erro_no], E_USER_WARNING);
            return false;
        }

        if (empty($this->font_color))
            $this->font_color = '#000000';

        //将16进制转为数组array(r,g,b)
        if (strlen($this->font_color) == 7) {
            $r = hexdec(substr($this->font_color, 1, 2));
            $g = hexdec(substr($this->font_color, 3, 2));
            $b = hexdec(substr($this->font_color, 5));
            $this->font_color = array($r, $g, $b);
        }

        $fontColor = $this->font_color;
        $fontColor = imagecolorallocate($this->image, $fontColor[0], $fontColor[1], $fontColor[2]);

        //设定图片混色模式
        imagealphablending($this->image, true);

        if (empty($this->font_ttf) || !is_file($this->font_ttf)) {//如果不加载字体，则使用默认字体
            $f = @imagestring(
                            $this->image, $this->font_size, $this->img_water_x, $this->img_water_y, $this->font_text, $fontColor
            );
        } else {//如果加载字体则使用字体显示
            $f = @imagettftext(
                            $this->image, $this->font_size, $this->font_angle, $this->img_water_x, $this->img_water_y, $fontColor, $this->font_ttf, $this->font_text
            );
        }

        $_rtn = self::saveImage($this->image, $fileInfo['extension'], $savePath, substr($this->saveName, 0, -(strlen($fileInfo['extension']) + 1)));
        imagedestroy($this->image);

        return $_rtn;
    }

    //图片一般缩略图方法 $cut=是否裁图
    function shrinkImage($fname='', $savePath='', $cut=false) {
        if (empty($fname))
            $fname = $this->imagepath;
        
        if ($fileInfo = self::isFileTrue($fname))
            $this->image = self::readImage($fileInfo['extension'], $fname); //取原图片信息
        
        self::isResource($this->image);

        if (empty($this->shrink_type) && $fileInfo['extension'])
            $this->shrink_type = $fileInfo['extension'];
        if (empty($this->saveName))
            $this->saveName = "shrink_{$fileInfo['basename']}";

        if (empty($savePath)) {
            $savePath = empty($this->savePath) ? $fname : $this->savePath;
        } elseif (!self::is_write($savePath)) {
            return false;
        }

        $image_width = imagesx($this->image);
        $image_height = imagesy($this->image);

        if ($this->shrink_width < 0) {
            $this->shrink_width = ceil($image_width / abs($this->shrink_width));
        } elseif ($this->shrink_width == 0) {
            $this->shrink_width = $image_width;
        }

        if ($this->shrink_height < 0) {
            $this->shrink_height = ceil($image_height / abs($this->shrink_height));
        } elseif ($this->shrink_height == 0) {
            $this->shrink_height = $image_height;
        }
        
        // 缩图比例
        $r_ratio = $this->shrink_width / $this->shrink_height;
        
        // 实图比例
        $d_ratio = $image_width / $image_height;
        
        // 裁图
        if ($cut) {
            // 高度优先
            if ($d_ratio >= $r_ratio) {
                $rinkImage = imagecreatetruecolor($this->shrink_width, $this->shrink_height);
                imagecopyresampled($rinkImage, $this->image, 0, 0, 0, 0, $this->shrink_width, $this->shrink_height, $image_height * $r_ratio, $image_height);
            } else {
                $rinkImage = imagecreatetruecolor($this->shrink_width, $this->shrink_height);
                imagecopyresampled($rinkImage, $this->image, 0, 0, 0, 0, $this->shrink_width, $this->shrink_height, $image_width, $image_width / $r_ratio);                
            }
        } else {
            if ($d_ratio >= $r_ratio) {
                $rinkImage = imagecreatetruecolor($this->shrink_width, $this->shrink_width / $d_ratio);
                imagecopyresampled($rinkImage, $this->image, 0, 0, 0, 0, $this->shrink_width, $this->shrink_width / $d_ratio, $image_width, $image_height);
            } else {
                $rinkImage = imagecreatetruecolor($this->shrink_height * $d_ratio, $this->shrink_height);
                imagecopyresampled($rinkImage, $this->image, 0, 0, 0, 0, $this->shrink_height * $d_ratio, $this->shrink_height, $image_width, $image_height);                
            }
        }
        
        //$rinkImage = self::createAlpha($this->shrink_width, $this->shrink_height);
        //imagecopyresized($rinkImage, $this->image, 0, 0, 0, 0, $this->shrink_width, $this->shrink_height, $image_width, $image_height);
        
        $shrink = self::saveImage($rinkImage, $this->shrink_type, $savePath, substr($this->saveName, 0, -(strlen($this->shrink_type) + 1)));
        imagedestroy($this->image);
        imagedestroy($rinkImage);
        return $shrink;
    }

    // 图片优化缩略方法
    public function resizeImage($src_file, $savePath, $fill=false) {
        // 保存路径是否可写
        if (!self::is_write($savePath))
            return false;
        
        // 都为空
        if ($this->shrink_width < 1 && $this->shrink_height < 1) {
            self::$erro_no = 8;
            trigger_error('Image: ' . self::$erro_msg[self::$erro_no], E_USER_WARNING);
            return false;
        }
        
        // 图像类型
        function_exists('exif_imagetype') && $type = exif_imagetype($src_file);

        !isset($type) && ((list(,, $type, ) = getimagesize($src_file)) !== false);
        $support_type = array('1' => 'gif', '2' => 'jpg', '3' => 'png', '6' => 'bmp');

        if ($fileInfo = self::isFileTrue($src_file)) {
            $this->image = self::readImage($support_type[$type], $src_file); //取原图片信息
        } else {
            return false;
        }

        if (!self::isResource($this->image))
            return false;

        if (empty($this->shrink_type)) {
            $this->shrink_type = $support_type[$type];
        }
        
        // 
        if ($this->shrink_width < 1) $this->shrink_width = imagesx($this->image);
        if ($this->shrink_height < 1) $this->shrink_height = imagesy($this->image);
        
        if ($fill == true) {
            $new_img = self::backFill($this->image, $this->shrink_width, $this->shrink_height);
        } else {
            $new_img = self::shrinkRatio($this->image, $this->shrink_width, $this->shrink_height);
        }
        
        return self::saveImage($new_img, $this->shrink_type, $savePath, substr($this->saveName, 0, -(strlen($this->shrink_type) + 1)));
    }

    // 生成指定大小图片
    public function makeImg($src_file, $savePath, $maxSize = null) {
        // 保存路径是否可写
        if (!self::is_write($savePath))
            return false;

        // 图像类型
        function_exists('exif_imagetype') && $type = exif_imagetype($src_file);
        !isset($type) && ((list(,, $type, ) = getimagesize($src_file)) !== false);
        $support_type = array('1' => 'gif', '2' => 'jpg', '3' => 'png', '6' => 'bmp');

        if ($fileInfo = self::isFileTrue($src_file)) {
            $this->image = self::readImage($support_type[$type], $src_file); // 取原图片信息
        } else {
            return false;
        }

        self::isResource($this->image);
        $image = self::shotImg($this->image, $maxSize);
        $_rtn = self::saveImage($image, $support_type[$type], $savePath, substr(basename($src_file), 0, -(strlen($support_type[$type]) + 1)));
        imagedestroy($this->image);
        return $_rtn;
    }

    //通过缩小原图方式, 获得MAX尺寸的最大图像  
    public static function shotImg($image, $maxs = null, $ratio = null) {
        if (!self::isResource($image))
            return false;

        $width_orig = imagesx($image);
        $height_orig = imagesy($image);

        empty($ratio) ? $ratio = 2 / 3 : ''; // 默认缩小比例
        empty($maxs) ? $maxs = ceil(max($width_orig, $height_orig) * $ratio) : '';
        $MAXwidth = $width_orig > $maxs ? $maxs : $width_orig;
        $MAXheight = $height_orig > $maxs ? $maxs : $height_orig;

        if ($MAXwidth && ($width_orig < $height_orig)) {
            $MAXwidth = ceil(($MAXheight / $height_orig) * $width_orig);
        } else {
            $MAXheight = ceil(($MAXwidth / $width_orig) * $height_orig);
        }

        $image_p = self::createAlpha($MAXwidth, $MAXheight);
        imagecopyresampled($image_p, $image, 0, 0, 0, 0, $MAXwidth, $MAXheight, $width_orig, $height_orig);
        return $image_p;
    }

    // 按比例缩小原图
    static function shrinkRatio($dst_img, $shrink_w, $shrink_h) {
        if (!self::isResource($dst_img) || empty($shrink_w) || empty($shrink_h))
            return false;

        $w = imagesx($dst_img);
        $h = imagesy($dst_img);
        $ratio_w = 1.0 * $shrink_w / $w;
        $ratio_h = 1.0 * $shrink_h / $h;
        $ratio = 1.0;

        if (($ratio_w < 1 && $ratio_h < 1) || ($ratio_w > 1 && $ratio_h > 1)) {
            $ratio = $ratio_w < $ratio_h ? $ratio_h : $ratio_w;
            $inter_w = ceil($shrink_w / $ratio);
            $inter_h = ceil($shrink_h / $ratio);

            // 按指定比例裁剪
            $inter_img = self::createAlpha($inter_w, $inter_h);
            imagecopy($inter_img, $dst_img, 0, 0, 0, 0, $inter_w, $inter_h);
        } else {
            $ratio = $ratio_h > $ratio_w ? $ratio_h : $ratio_w;
            $inter_w = ceil($w * $ratio);
            $inter_h = ceil($h * $ratio);

            // 按原图缩放比例后裁剪
            $inter_img = self::createAlpha($inter_w, $inter_h);
            imagecopyresampled($inter_img, $dst_img, 0, 0, 0, 0, $inter_w, $inter_h, $w, $h);
        }

        $new_img = self::createAlpha($shrink_w, $shrink_h);
        imagecopyresampled($new_img, $inter_img, 0, 0, 0, 0, $shrink_w, $shrink_h, $inter_w, $inter_h);
        return $new_img;
    }

    //以填充方式生成缩略图
    static function backFill($dst_img, $shrink_w, $shrink_h, $bcolor = '') {
        if (!self::isResource($dst_img) || empty($shrink_w) || empty($shrink_h))
            return false;

        $w = imagesx($dst_img);
        $h = imagesy($dst_img);
        
        // 缩略高大于原图高
        if ($shrink_h > $h) $shrink_h = $h;
        
        $shrink_wH = $shrink_w / $shrink_h;
        $srcWH = $w / $h;
        
        // 计算缩小高宽
        $ftoH = $shrink_h;
        $ftoW = $ftoH * ($w / $h);
        if ($shrink_wH <= $srcWH) {
            $ftoW = $shrink_w;
            $ftoH = $ftoW * ($h / $w);
        }        

        $bgcolor = array(255, 255, 255);
        if (strlen($bcolor) == 7) {
            $r = hexdec(substr($bcolor, 1, 2));
            $g = hexdec(substr($bcolor, 3, 2));
            $b = hexdec(substr($bcolor, 5));
            $bgcolor = array($r, $g, $b);
        }

        $inter_img = self::createAlpha($shrink_w, $shrink_h);
        $backcolor = imagecolorallocate($inter_img, $bgcolor[0], $bgcolor[1], $bgcolor[2]); //填充的背景颜色 
        ImageFilledRectangle($inter_img, 0, 0, $shrink_w, $shrink_h, $backcolor);         
        
        // 等比缩小
        if ($w > $shrink_w || $h > $shrink_h) {
            if ($ftoW < $shrink_w) {
                imagecopyresampled($inter_img, $dst_img, ($shrink_w - $ftoW) / 2, 0, 0, 0, $ftoW, $ftoH, $w, $h);
            } else if ($ftoH < $shrink_h) {
                imagecopyresampled($inter_img, $dst_img, 0, ($shrink_h - $ftoH) / 2, 0, 0, $ftoW, $ftoH, $w, $h);
            } else {
                imagecopyresampled($inter_img, $dst_img, 0, 0, 0, 0, $ftoW, $ftoH, $w, $h);
            }
        } else {
            ImageCopyMerge($inter_img, $dst_img, ($shrink_w - $w) / 2, ($shrink_h - $h) / 2, 0, 0, $w, $h, 100);
        }

        return $inter_img;
    }

    //读取水印文件(必须操作)
    function read_waterImg($Cimgu = '', $iserror = false) {
        if (empty($Cimgu))
            $Cimgu = './water.jpg';
        $this->waterPath = $Cimgu;

        if ($Cimginfo = self::isFileTrue($this->waterPath, false)) {
            $this->img_water = self::readImage($Cimginfo['extension'], $this->waterPath); //取水印图片信息
            $this->img_width = imagesx($this->img_water);
            $this->img_Height = imagesy($this->img_water);
        } elseif (boolVal($iserror)) {
            self::$erro_no = 4;
            trigger_error('Image: ' . self::$erro_msg[self::$erro_no], E_USER_WARNING);
        }

        return $this;
    }

    // 创建透明画布
    private static function createAlpha($w, $h) {
        if (empty($w) || empty($h))
            return false;

        $alphaimg = imagecreatetruecolor($w, $h);
        imagealphablending($alphaimg, true);
        imageSaveAlpha($alphaimg, true);
        $bgc = imageColorAllocate($alphaimg, 0, 0, 0);
        imagefilledrectangle($alphaimg, 0, 0, $w, $h, $bgc);
        imagecolortransparent($alphaimg, $bgc);

        return $alphaimg;
    }

    // 创建目录
    private static function createFolder($path, $mode = 0777) {
        $status = false;
        if ($path && !is_dir($path) && ($status = mkdir($path, $mode, true)))
            chmod($path, $mode);
        return $status;
    }

    private static function is_write($path, $iserror = true) {
        if (!is_dir($path) && !is_file($path)) {
            if (!self::createFolder($path)) {
                self::$erro_no = 9;
                boolVal($iserror) && trigger_error('Image: ' . self::$erro_msg[self::$erro_no], E_USER_WARNING);
                return false;
            }
        }

        /* 检查目录是否可写 */
        if (!@is_writable($path)) {
            self::$erro_no = 10;
            boolVal($iserror) && trigger_error('Image: ' . self::$erro_msg[self::$erro_no], E_USER_WARNING);
            return false;
        }

        return true;
    }

    private static function isFileTrue($fname, $iserror = true) {
        if (!is_file($fname)) {
            self::$erro_no = 11;
            boolVal($iserror) && trigger_error('Image: ' . self::$erro_msg[self::$erro_no], E_USER_WARNING);
            return false;
        }

        $fileInfo = pathinfo($fname);
        return $fileInfo;
    }

    private static function isResource($im, $iserror = true) {
        if (!is_resource($im)) {
            self::$erro_no = 12;
            boolVal($iserror) && trigger_error('Image: ' . self::$erro_msg[self::$erro_no], E_USER_WARNING);
            return false;
        }
        return true;
    }

    // 设定类中指定变量名的值，如果改变量不属于这个类，将throw一个exception
    function set($var, $value) {
        if (in_array($var, get_object_vars($this))) {
            $this->$var = $value;
        } else {
            self::$erro_no = 13;
            trigger_error('Image: ' . self::$erro_msg[self::$erro_no], E_USER_WARNING);
            return false;
        }
        return $this;
    }

    // 兼容写法 
    function setsaveName($t) {
        return $this->set('saveName', strtolower($t));
    }

    function setsavePath($t) {
        return $this->set('savePath', $t);
    }

    function setupFileSize($t) {
        return $this->set('upfile_size', $t);
    }

    function setShrinkType($t) {
        return $this->set('shrink_type', $t);
    }

    function setShrinkWidth($t) {
        return $this->set('shrink_width', $t);
    }

    function setShrinkHeight($t) {
        return $this->set('shrink_height', $t);
    }

    function setFontTtf($t) {
        return $this->set('font_ttf', $t);
    }

    function setFontText($t) {
        return $this->set('font_text', $t);
    }

    function setFontColor($t) {
        return $this->set('font_color', $t);
    }

    function setFontAngle($t) {
        return $this->set('shrink_height', $t);
    }

    function setFontSize($t) {
        return $this->set('font_size', $t);
    }

    function setFileType($t) {
        if (!is_array($t)) {
            $t = array($t);
        }
        return $this->set('upfile_type', array_merge($this->upfile_type, $t));
    }

    function setCheckType($t = true) {
        return $this->set('check_type', $t);
    }

    function setImgWaterPosType($t) {
        return $this->set('img_water_PosType', $t);
    }

    private function WaterPos($Type) {
        switch ($Type) {
            case 0://左上角
                $this->img_water_x = 10;
                $this->img_water_y = 10;
                break;
            case 1://右上角
                $this->img_water_x = imagesx($this->image) - $this->img_width - 10;
                $this->img_water_y = 10;
                break;
            case 2://居中
                $this->img_water_x = ceil((imagesx($this->image) - $this->img_width) / 2);
                $this->img_water_y = ceil((imagesy($this->image) - $this->img_Height) / 2);
                break;
            case 3://左下角
                $this->img_water_x = 10;
                $this->img_water_y = imagesy($this->image) - $this->img_Height - 10;
                break;
            case 4://右下角
                $this->img_water_x = imagesx($this->image) - $this->img_width - 10;
                $this->img_water_y = imagesy($this->image) - $this->img_Height - 10;
                break;
        }
        return $this;
    }

    public static function readImage($imgType, $imgPath) {
        self::isFileTrue($imgPath);
        $imgType = self::getTypeName($imgPath);
        switch (strtolower($imgType)) {
            case 'gif':
                $img = @imagecreatefromgif($imgPath);
                break;
            case 'jpg':
                $img = @imagecreatefromjpeg($imgPath);
                break;
            case 'png':
                $img = @imagecreatefrompng($imgPath);
                break;
            case 'bmp':
                $img = @imagecreatefrombmp($imgPath); //自定义函数
                break;
            default:
                $img = @imagecreatefromjpeg($imgPath);
        }
        return $img;
    }

    public static function getTypeName($path) {
        $info = getimagesize($path);

        if (isset(self::$image_type_idents[$info[2]]))
            return self::$image_type_idents[$info[2]][0];

        // 没有预定义时自动截取mime值
        if (isset($info['mime']) && $info['mime']) {
            list(, $tname) = explode('/', $info['mime'], 2);
            $tname = strtolower($tname);
            if (in_array($tname, array('jpg', 'jpeg', 'pjpeg'))) {
                $tname = 'jpg';
            } else if (in_array($tname, array('png', 'x-png'))) {
                $tname = 'png';
            }
            return $tname;
        }

        return null;
    }

    public static function saveImage($im, $imgType, $savePath, $saveName) {
        // 路径是否存在
        if (!self::is_write($savePath))
            return false;

        // 构建全路径
        strtolower($imgType) == 'bmp' ? $imgType = 'jpg' : '';
        $path = "{$savePath}{$saveName}." . strtolower($imgType);

        self::isResource($im);
        switch (strtolower($imgType)) {
            case 'gif':
                $img = @imagegif($im, $path, 100);
                break;
            case 'jpeg':
                $img = @imagejpeg($im, $path, 100);
                break;
            case 'png':
                imagesavealpha($im, true);
                $img = @imagepng($im, $path);
                break;
            case 'jpg':
                $img = @imagejpeg($im, $path, 100);
                break;
            default:
                $img = @imagejpeg($im, $path, 100);
        }

        if (!$img)
            return false;
        return $path;
    }

    function getErrmsg() {
        return self::$erro_msg[self::$erro_no];
    }

}

?>