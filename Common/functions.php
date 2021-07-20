<?php

/**
 +------------------------------------------------------------------------------
 * PLFrame公共函数库
 +------------------------------------------------------------------------------
 * @category PLFrame
 * @package  Common
 * @author   pengzl<pengzl_gz@163.com>
 * @version  $Id: functions.php 1 2014-09-11 02:12:40Z pengzl $
 +------------------------------------------------------------------------------
 */

/**
 +----------------------------------------------------------
 * 错误输出
 * 在调试模式下面会输出详细的错误信息
 * 否则就定向到指定的错误页面
 +----------------------------------------------------------
 * @param mixed $error 错误信息 可以是数组或者字符串
 * 数组格式为异常类专用格式 不接受自定义数组格式
 +----------------------------------------------------------
 * @return void
 +----------------------------------------------------------
 */
function halt($error) {
    $e = array();
    if (C('DEBUG_MODE')) {
        // 调试模式下输出错误信息
        if (!is_array($error)) {
            $trace = debug_backtrace();
            $e['message'] = $error;
            $e['file'] = isset($trace[0]['file']) ? $trace[0]['file'] : '';
            $e['class'] = isset($trace[0]['class']) ? $trace[0]['class'] : '';
            $e['function'] = isset($trace[0]['function']) ? $trace[0]['function'] : '';
            $e['line'] = $trace[0]['line'];
            $traceInfo = '';
            $time = date('y-m-d H:i:m');

            foreach ($trace as $t) {
                $traceInfo .= '[' . $time . '] ' . $t['file'] . ' (' . $t['line'] . ') ';
                $traceInfo .= ( isset($t['class']) ? $t['class'] : '') . (isset($t['type']) ? $t['type'] : '') . (isset($t['function']) ? $t['function'] : '') . '(';
                $traceInfo .= implode(', ', $t['args']);
                $traceInfo .= ')<br/>';
            }
            $e['trace'] = $traceInfo;
        } else {
            $e = $error;
        }
        if (C('EXCEPTION_TMPL_FILE')) {
            // 定义了异常页面模板
            include C('EXCEPTION_TMPL_FILE');
        } else {
            // 使用默认的异常模板文件
            include PLFRAME_PATH . DS . 'Tpl' . DS . 'OutPutERMSG.tpl.php';
        }
    } else {
        // 否则定向到错误页面
        $error_page = C('ERROR_PAGE');
        if (!empty($error_page)) {
            redirect($error_page);
        } else {
            $e['message'] = C('ERROR_MESSAGE');
            if (C('EXCEPTION_TMPL_FILE')) {
                // 定义了异常页面模板
                include C('EXCEPTION_TMPL_FILE');
            } else {
                // 使用默认的异常模板文件
                include PLFRAME_PATH . DS . 'Tpl' . DS . 'OutPutERMSG.tpl.php';
            }
        }
    }
    exit();
}

/**
 +----------------------------------------------------------
 * 变量输出
 +----------------------------------------------------------
 * @param string $var 变量名
 * @param string $label 显示标签
 * @param string $echo 是否显示
 +----------------------------------------------------------
 * @return string
 +----------------------------------------------------------
 */
function dump($var = '', $label = null, $echo = true, $strict = true) {
    $label = ($label === null) ? '' : rtrim($label) . ' ';

    if (!$strict) {
        if (ini_get('html_errors')) {
            $output = print_r($var, true);
            $output = '<pre>' . $label . htmlspecialchars($output, ENT_QUOTES, C('OUTPUT_CHARSET') ? C('OUTPUT_CHARSET') : 'utf-8') . '</pre>';
        } else {
            $output = $label . ' : ' . print_r($var, true);
        }
    } else {
        ob_start();
        var_dump($var);
        $output = ob_get_clean();
        if (!extension_loaded('xdebug')) {
            $output = preg_replace('/\]\=\>\n(\s+)/m', '] => ', $output);
            $output = '<pre>'
                    . $label
                    . htmlspecialchars($output, ENT_QUOTES, C('OUTPUT_CHARSET') ? C('OUTPUT_CHARSET') : 'utf-8')
                    . '</pre>';
        }
    }

    if ($echo) {
        echo($output);
        return null;
    }

    return $output;
}

/**
 +----------------------------------------------------------
 * 对象实例注册 支持调用类的方法
 +----------------------------------------------------------
 * @param string $className 对象类名
 * @param string $method 类的方法名
 +----------------------------------------------------------
 * @return object
 +----------------------------------------------------------
 */
function get_instance($className = '', $method = '', $args = array()) {
    static $_instance = array();
    if (empty($args)) {
        $identify = $className . $method;
    } else {
        $identify = $className . $method . to_guid_string($args);
    }

    if (!$className) {
        return $_instance; // 返回已注册的对象实例集
    }

    if (!isset($_instance[$identify])) {
        if (class_exists($className)) {
            $o = new $className();
            if (method_exists($o, $method)) {
                if (!empty($args)) {
                    $_instance[$identify] = call_user_func_array(array(&$o, $method), $args);
                } else {
                    $_instance[$identify] = $o->$method();
                }
            } else {
                $_instance[$identify] = $o;
            }
        } else {
            halt(L('_CLASS_NOT_EXIST_'));
        }
    }
    return $_instance[$identify];
}

/**
 +----------------------------------------------------------
 * 获取运行时间差(秒)
 +----------------------------------------------------------
 * @param string $time 开始时间
 +----------------------------------------------------------
 * @return flaut
 +----------------------------------------------------------
 */
function get_runtime($time) {
    if (is_array($time)) {
        return round(($time[1] - $time[0]), 4);
    }
    !$time ? $time = microtime(TRUE) : '';
    return round((microtime(TRUE) - $time), 4);
}

/**
 +----------------------------------------------------------
 * 系统自动加载当前项目的类
 * 并且支持配置自动加载路径
 +----------------------------------------------------------
 * @param string $classname 对象类名
 +----------------------------------------------------------
 * @return void
 +----------------------------------------------------------
 */
/*
  function __autoload($classname)
  {
  if (C('AUTO_LOAD_PATH'))
  {
  $_str  = array();
  $paths = explode(',',C('AUTO_LOAD_PATH'));
  foreach((array)$paths as $path)
  {
  $classfile = pathAnaly($path.$classname);
  if (is_file($classfile)){
  if (import($path.$classname)) {
  // 如果加载类成功则返回
  return true;
  }
  } else {
  $_str[] = "{$path}{$classname}";
  }
  }

  if (count($_str))
  {
  throw_exception("类{$classname}加载失败,".implode('加载路径:', $_str));
  }
  }
  return ;
  }
 */

/**
 +----------------------------------------------------------
 * 自动转换字符集 支持数组转换
 * 需要 iconv 或者 mb_string 模块支持
 * 如果 输出字符集和模板字符集相同则不进行转换
 +----------------------------------------------------------
 * @param string $fContents 需要转换的字符串
 +----------------------------------------------------------
 * @return string
 +----------------------------------------------------------
 */
function auto_charset($fContents, $from = '', $to = '') {
    $from = $from ? $from : C('TEMPLATE_CHARSET');
    $to = $to ? $to : C('OUTPUT_CHARSET');

    if (strtoupper($from) === strtoupper($to) || empty($fContents) || (is_scalar($fContents) && !is_string($fContents))) {
        // 如果编码相同或者非字符串标量则不转换
        return $fContents;
    }

    $from = strtoupper($from) == 'UTF8' ? 'utf-8' : $from;
    $to = strtoupper($to) == 'UTF8' ? 'utf-8' : $to;
    if (is_string($fContents)) {
        if (function_exists('mb_convert_encoding')) {
            return mb_convert_encoding($fContents, $to, $from);
        } elseif (function_exists('iconv')) {
            return iconv($from, $to, $fContents);
        } else {
            halt(L('_NO_AUTO_CHARSET_'));
            return $fContents;
        }
    } elseif (is_array($fContents)) {
        foreach ($fContents as $key => $val) {
            $_key = auto_charset($key, $from, $to);
            $fContents[$_key] = auto_charset($val, $from, $to);
            if ($key != $_key) {
                unset($fContents[$key]);
            }
        }
        return $fContents;
    } elseif (is_object($fContents)) {
        $vars = get_object_vars($fContents);
        foreach ($vars as $key => $val) {
            $fContents->$key = auto_charset($val, $from, $to);
        }
        return $fContents;
    } else {
        //halt('系统不支持对'.gettype($fContents).'类型的编码转换！');
        return $fContents;
    }
    return $fContents;
}

/**
 +----------------------------------------------------------
 * 类文件路径解析
 +----------------------------------------------------------
 * @param string $class 类路径
 * @param string $baseUrl 路径前缀
 * @param string $ext 类文件后缀
 +----------------------------------------------------------
 * @return string
 +----------------------------------------------------------
 */
function pathAnaly($class, $baseUrl = '', $ext = '.class.php') {
    $isPath = true;
    if (empty($baseUrl)) {
        $baseUrl = dirname(CLASS_DIR);  // 默认方式为调用具体项目应用类库  
        $isPath = false;
    }

    list($class_prefix, ) = explode('.', $class);

    if ('*' == $class_prefix || $isPath) { // 多级目录加载支持 需另行扩展
    } elseif ('@' == $class_prefix) { // 加载当前项目应用类库
        $class = str_replace('@', APP_NAME, $class);
        $class = str_replace(DS, '', str_replace(APP_NAME . '.', substr(CLASS_DIR, strlen($baseUrl), strlen(CLASS_DIR) - 1) . '.', $class));
    } elseif (in_array(strtolower($class_prefix), array('plframe', 'db', 'smarty', 'net', 'util'))) { // 加载基类库或者公共类库
        $baseUrl = LIB_DIR;
    } elseif (in_array(strtolower($class_prefix), array('include', 'vendor'))) { // 加载其他项目应用类库
        $baseUrl = PLFRAME_PATH;
    }

    if (substr($baseUrl, -1) != DS)
        $baseUrl .= DS;
    
    return $baseUrl . str_replace('.', DS, $class) . $ext;
}

/**
 +----------------------------------------------------------
 * 导入所需的类库 类同java的Import
 +----------------------------------------------------------
 * @param string $class 类库命名空间字符串
 * @param string $baseUrl 起始路径
 * @param string $appName 项目名
 * @param string $ext 导入的文件扩展名
 +----------------------------------------------------------
 * @return mixed
 +----------------------------------------------------------
 */
function import($class, $baseUrl = '', $ext = '.class.php') {
    static $_file = array();
    static $_class = array();
    $ext = $ext ? $ext : '.class.php';

    // 取类名 及 合法性检测
    $_tmpName = explode('.', $class);
    $_className = ucfirst($_tmpName[count($_tmpName) - 1]);
    preg_match('/[^a-z0-9\-_.*]/i', $_className) ? throw_exception('import非法的类名或者目录！') : '';

    //解析路径
    $classfile = pathAnaly($class, $baseUrl, $ext);

    // 导入匹配的文件
    $classList = glob($classfile);

    if ($classList) {
        foreach ($classList as $key => $val) {
            $identify = to_guid_string($val);

            if (isset($_file[$identify]))
                continue;

            // 冲突检测
            $_className = basename($val, $ext);
            if ($ext == '.class.php' && (class_exists($_className, false) || interface_exists($_className, false) || isset($_class[$class]))
            ) {
                continue;
            }

            $_class[$class] = $val;

            // 导入类库文件
            if (require($val)) {
                $_file[$identify] = true;
            } else {
                throw_Exception("加载类文件:{$val} 失败");
            }
        }
        return true;
    }
    return false;
}

/**
 +----------------------------------------------------------
 * 自定义异常处理
 +----------------------------------------------------------
 * @param string $msg 错误信息
 * @param string $type 异常类型 默认为 PLFException
 * 如果指定的异常类不存在，则直接输出错误信息
 +----------------------------------------------------------
 * @return void
 +----------------------------------------------------------
 */
function throw_exception($msg, $type = 'PLFException', $code = 0) {
    if (isAjax()) {
        header('Content-Type:text/html; charset=' . C('OUTPUT_CHARSET'));
        exit(json_encode(array('msg' => $msg)));
    }

    if (class_exists($type, false)) {
        throw new Exception($msg, $code);
    } else {
        // 异常类型不存在则输出错误信息字串
        halt($msg);
    }
}

function errorExceptionHandler($code, $string, $file, $line) {
    throw new Exception($string, $code);
}

/**
 +----------------------------------------------------------
 * 根据PHP各种类型变量生成唯一标识号
 +----------------------------------------------------------
 * @param mixed $mix 变量
 +----------------------------------------------------------
 * @return string
 +----------------------------------------------------------
 */
function to_guid_string($mix) {
    if (is_object($mix) && function_exists('spl_object_hash')) {
        return spl_object_hash($mix);
    } elseif (is_resource($mix)) {
        $mix = get_resource_type($mix) . strval($mix);
    } else {
        $mix = serialize($mix);
    }
    return md5($mix);
}

/**
 +----------------------------------------------------------
 * 获得迭代因子  使用foreach遍历对象
 +----------------------------------------------------------
 * @param mixed $values 对象元素
 +----------------------------------------------------------
 * @return string
 +----------------------------------------------------------
 */
function get_iterator($values) {
    return new ArrayObject($values);
}

/**
 +----------------------------------------------------------
 * 判断是否为对象实例
 +----------------------------------------------------------
 * @param mixed $object 实例对象
 * @param mixed $className 对象名
 +----------------------------------------------------------
 * @return boolean
 +----------------------------------------------------------
 */
function is_instance_of($object, $className) {
    return $object instanceof $className;
}

/**
 +----------------------------------------------------------
 * 字符串截取，支持中文和其他编码
 +----------------------------------------------------------
 * @static
 * @access public
 +----------------------------------------------------------
 * @param string $str 需要转换的字符串
 * @param string $start 开始位置 从零开始
 * @param string $length 截取长度
 * @param string $suffix 截断显示字符
 * @param string $charset 编码格式
 +----------------------------------------------------------
 * @return string
 +----------------------------------------------------------
 */
function msubstr($str, $start = 0, $length = 0, $suffix = false, $charset = 'utf-8') {
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
    return $suffix ? $slice . '……' : $slice;
}

/**
 +----------------------------------------------------------
 * 产生随机字串，可用来自动生成密码 默认长度6位 字母和数字混合
 +----------------------------------------------------------
 * @param string $len 长度
 * @param string $type 字串类型
 * 0 字母 1 数字 其它 混合
 * @param string $addChars 额外字符
 +----------------------------------------------------------
 * @return string
 +----------------------------------------------------------
 */
function rand_string($len = 6, $type = '', $addChars = '') {
    $str = '';
    switch ($type) {
        case 0:
            $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
            break;

        case 1:
            $chars = str_repeat('0123456789', 3);
            break;

        case 2:
            $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ' . $addChars;
            break;

        case 3:
            $chars = 'abcdefghijklmnopqrstuvwxyz' . $addChars;
            break;

        case 4:
            $chars = '们以我到他会作时要动国产的一是工就年阶义发成部民可出能方进在了不和有大这主中人上为来分生对于学下级地个用同行面说种过命度革而多子后自社加小机也经力线本电高量长党得实家定深法表着水理化争现所二起政三好十战无农使性前等反体合斗路图把结第里正新开论之物从当两些还天资事队批点育重其思与间内去因件日利相由压员气业代全组数果期导平各基或月毛然如应形想制心样干都向变关问比展那它最及外没看治提五解系林者米群头意只明四道马认次文通但条较克又公孔领军流入接席位情运器并飞原油放立题质指建区验活众很教决特此常石强极土少已根共直团统式转别造切九你取西持总料连任志观调七么山程百报更见必真保热委手改管处己将修支识病象几先老光专什六型具示复安带每东增则完风回南广劳轮科北打积车计给节做务被整联步类集号列温装即毫知轴研单色坚据速防史拉世设达尔场织历花受求传口断况采精金界品判参层止边清至万确究书术状厂须离再目海交权且儿青才证低越际八试规斯近注办布门铁需走议县兵固除般引齿千胜细影济白格效置推空配刀叶率述今选养德话查差半敌始片施响收华觉备名红续均药标记难存测士身紧液派准斤角降维板许破述技消底床田势端感往神便贺村构照容非搞亚磨族火段算适讲按值美态黄易彪服早班麦削信排台声该击素张密害侯草何树肥继右属市严径螺检左页抗苏显苦英快称坏移约巴材省黑武培著河帝仅针怎植京助升王眼她抓含苗副杂普谈围食射源例致酸旧却充足短划剂宣环落首尺波承粉践府鱼随考刻靠够满夫失包住促枝局菌杆周护岩师举曲春元超负砂封换太模贫减阳扬江析亩木言球朝医校古呢稻宋听唯输滑站另卫字鼓刚写刘微略范供阿块某功套友限项余倒卷创律雨让骨远帮初皮播优占死毒圈伟季训控激找叫云互跟裂粮粒母练塞钢顶策双留误础吸阻故寸盾晚丝女散焊功株亲院冷彻弹错散商视艺灭版烈零室轻血倍缺厘泵察绝富城冲喷壤简否柱李望盘磁雄似困巩益洲脱投送奴侧润盖挥距触星松送获兴独官混纪依未突架宽冬章湿偏纹吃执阀矿寨责熟稳夺硬价努翻奇甲预职评读背协损棉侵灰虽矛厚罗泥辟告卵箱掌氧恩爱停曾溶营终纲孟钱待尽俄缩沙退陈讨奋械载胞幼哪剥迫旋征槽倒握担仍呀鲜吧卡粗介钻逐弱脚怕盐末阴丰雾冠丙街莱贝辐肠付吉渗瑞惊顿挤秒悬姆烂森糖圣凹陶词迟蚕亿矩康遵牧遭幅园腔订香肉弟屋敏恢忘编印蜂急拿扩伤飞露核缘游振操央伍域甚迅辉异序免纸夜乡久隶缸夹念兰映沟乙吗儒杀汽磷艰晶插埃燃欢铁补咱芽永瓦倾阵碳演威附牙芽永瓦斜灌欧献顺猪洋腐请透司危括脉宜笑若尾束壮暴企菜穗楚汉愈绿拖牛份染既秋遍锻玉夏疗尖殖井费州访吹荣铜沿替滚客召旱悟刺脑措贯藏敢令隙炉壳硫煤迎铸粘探临薄旬善福纵择礼愿伏残雷延烟句纯渐耕跑泽慢栽鲁赤繁境潮横掉锥希池败船假亮谓托伙哲怀割摆贡呈劲财仪沉炼麻罪祖息车穿货销齐鼠抽画饲龙库守筑房歌寒喜哥洗蚀废纳腹乎录镜妇恶脂庄擦险赞钟摇典柄辩竹谷卖乱虚桥奥伯赶垂途额壁网截野遗静谋弄挂课镇妄盛耐援扎虑键归符庆聚绕摩忙舞遇索顾胶羊湖钉仁音迹碎伸灯避泛亡答勇频皇柳哈揭甘诺概宪浓岛袭谁洪谢炮浇斑讯懂灵蛋闭孩释乳巨徒私银伊景坦累匀霉杜乐勒隔弯绩招绍胡呼痛峰零柴簧午跳居尚丁秦稍追梁折耗碱殊岗挖氏刃剧堆赫荷胸衡勤膜篇登驻案刊秧缓凸役剪川雪链渔啦脸户洛孢勃盟买杨宗焦赛旗滤硅炭股坐蒸凝竟陷枪黎救冒暗洞犯筒您宋弧爆谬涂味津臂障褐陆啊健尊豆拔莫抵桑坡缝警挑污冰柬嘴啥饭塑寄赵喊垫丹渡耳刨虎笔稀昆浪萨茶滴浅拥穴覆伦娘吨浸袖珠雌妈紫戏塔锤震岁貌洁剖牢锋疑霸闪埔猛诉刷狠忽灾闹乔唐漏闻沈熔氯荒茎男凡抢像浆旁玻亦忠唱蒙予纷捕锁尤乘乌智淡允叛畜俘摸锈扫毕璃宝芯爷鉴秘净蒋钙肩腾枯抛轨堂拌爸循诱祝励肯酒绳穷塘燥泡袋朗喂铝软渠颗惯贸粪综墙趋彼届墨碍启逆卸航衣孙龄岭骗休借' . $addChars;
            break;

        default :
            // 默认去掉了容易混淆的字符oOLl和数字01，要添加请使用addChars参数
            $chars = 'ABCDEFGHIJKMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789' . $addChars;
    }

    // 位数过长重复字符串一定次数
    $len > 10 ? $chars = $type == 1 ? str_repeat($chars, $len) : str_repeat($chars, 5) : '';

    if ($type != 4) {
        $chars = str_shuffle($chars);
        $str = substr($chars, 0, $len);
    } else {
        // 中文随机字
        foreach (range(0, $len - 1) as $i) {
            $str .= msubstr($chars, floor(mt_rand(0, mb_strlen($chars, 'utf-8') - 1)), 1);
        }
    }

    return $str;
}

/**
 +----------------------------------------------------------
 * 获取登录验证码 默认为4位数字
 +----------------------------------------------------------
 * @param string $length 长度
 * @param string $mode   模式
 * 0 字母 1 数字 2 大写+其它 3小写+其它 4中文+其它 混合
 +----------------------------------------------------------
 * @return string
 +----------------------------------------------------------
 */
function build_verify($length = 4, $mode = 1) {
    return rand_string($length, $mode);
}

/**
 +----------------------------------------------------------
 * 创建项目基础目录结构
 +----------------------------------------------------------
 * @return void
 +----------------------------------------------------------
 */
function createAppDir() {
    // 没有创建项目目录的话自动创建
    if (!is_dir(APP_PATH))
        mk_dir(APP_PATH, 0777);

    if (is_writeable(APP_PATH)) {
        if (!is_dir(CLASS_DIR))
            mk_dir(CLASS_DIR, 0777);      // 创建项目类目录
        if (!is_dir(CODE_DIR))
            mk_dir(CODE_DIR, 0777);          // 创建项目逻辑文件目录
        if (!is_dir(TPL_DIR))
            mk_dir(TPL_DIR, 0777);           // 创建项目模板文件目录
        if (!is_dir(UPLOAD_PATH))
            mk_dir(UPLOAD_PATH, 0777);     // 创建项目上传文件目录
        if (!is_dir(LOG_PATH))
            mk_dir(LOG_PATH, 0777);          // 创建项目日志文件目录
        if (!is_dir(CONF_DIR))
            mk_dir(CONF_DIR, 0777);         // 创建项目配置文件目录
        if (!is_dir(SMARTY_COMPILE_DIR))
            mk_dir(SMARTY_COMPILE_DIR, 0777); // 创建项目SMARTY编译文件存放目录
        if (!is_dir(SMARTY_CACHE_DIR))
            mk_dir(SMARTY_CACHE_DIR, 0777);  // 创建项目SMARTY缓存目录
        if (!defined('CREATE_DIR_SECURE'))
            define('CREATE_DIR_SECURE', false);

        if (CREATE_DIR_SECURE) {
            if (!defined('DIR_SECURE_FILENAME'))
                define('DIR_SECURE_FILENAME', 'home,Home,home');
            if (!defined('HOME_PHP_CONTENT'))
                define('HOME_PHP_CONTENT', ' ');
            if (!defined('HOME_CLASS_CONTENT'))
                define('HOME_CLASS_CONTENT', ' ');
            if (!defined('HOME_HTML_CONTENT'))
                define('HOME_HTML_CONTENT', ' ');
            if (!defined('APP_CONFIG_CONTENT'))
                define('APP_CONFIG_CONTENT', ' ');

            mk_dir(CODE_DIR . URL_DEFAULT_NAME . DS, 0777);
            mk_dir(CLASS_DIR . URL_DEFAULT_NAME . DS, 0777);
            mk_dir(TPL_DIR . URL_DEFAULT_NAME . DS, 0777);
            mk_dir(CLASS_DIR . 'public' . DS, 0777);

            $_fileTemp = explode(',', DIR_SECURE_FILENAME);

            // 自动写入目录安全文件
            mk_file(CODE_DIR . URL_DEFAULT_NAME . DS . $_fileTemp[0] . '.php', HOME_PHP_CONTENT);
            mk_file(CLASS_DIR . URL_DEFAULT_NAME . DS . $_fileTemp[1] . '.class.php', HOME_CLASS_CONTENT);
            mk_file(TPL_DIR . URL_DEFAULT_NAME . DS . $_fileTemp[2] . '.html', HOME_HTML_CONTENT);
            mk_file(CONF_DIR . DS . 'config.inc.php', APP_CONFIG_CONTENT);
        }
    } else {
        header('Content-Type:text/html; charset=' . C('OUTPUT_CHARSET'));
        exit('项目目录不可写，目录无法自动生成！<BR />请手动生成项目目录!');
    }
}

/**
 +----------------------------------------------------------
 * stripslashes扩展 可用于数组
 +----------------------------------------------------------
 * @param mixed $value 变量
 +----------------------------------------------------------
 * @return mixed
 +----------------------------------------------------------
 */
if (!function_exists('stripslashes_deep')) {

    function stripslashes_deep($value) {
        $value = is_array($value) ? array_map(__FUNCTION__, $value) : stripslashes($value);
        return $value;
    }

}

/**
 +----------------------------------------------------------
 * trim扩展 可用于数组
 +----------------------------------------------------------
 * @param mixed $value 变量
 +----------------------------------------------------------
 * @return mixed
 +----------------------------------------------------------
 */
function trim_deep($value) {
    $value = is_array($value) ? array_map(__FUNCTION__, $value) : trim($value);
    return $value;
}

/**
 +----------------------------------------------------------
 * 获取语言定义
 +----------------------------------------------------------
 * @param string $name 变量
 * @param string $value 变量值
 +----------------------------------------------------------
 * @return mixed
 +----------------------------------------------------------
 */
function L($name = '', $value = null) {
    static $_lang = array();

    if (!empty($name) && !is_null($value)) {
        $_lang[strtoupper($name)] = $value;
    }

    if (is_array($name)) {
        $_lang = array_merge($_lang, array_change_key_case($name, CASE_UPPER));
        return $_lang;
    }

    if (!empty($name) && isset($_lang[strtoupper($name)])) {
        return $_lang[strtoupper($name)];
    }

    if ($name && !isset($_lang[strtoupper($name)])) {
        return '';
    }

    return $_lang;
}

/**
 +----------------------------------------------------------
 * 替代$_REQUEST
 +----------------------------------------------------------
 * @param string $name 变量
 * @param string $value 变量值
 +----------------------------------------------------------
 * @return mixed
 +----------------------------------------------------------
 */
function R($name = '') {
    if (empty($name)) {
        return;
    } elseif (in_array($name, array(URL_MODULE, URL_ACTION))) {
        return (G($name) == URL_DEFAULT_NAME) ? P($name) : G($name);
    } elseif (isset($_SERVER['REQUEST_METHOD']) && strtolower($_SERVER['REQUEST_METHOD']) == 'post') {
        return (P($name) == '' && !is_array(P($name))) ? G($name) : P($name);
    } elseif (isset($_SERVER['REQUEST_METHOD']) && strtolower($_SERVER['REQUEST_METHOD']) == 'get') {
        return (G($name) == '' && !is_array(G($name))) ? P($name) : G($name);
    } elseif (isset($_REQUEST[$name])) {
        return $_REQUEST[$name];
    } else {
        return;
    }
}

/**
 +----------------------------------------------------------
 * 替代$_POST
 +----------------------------------------------------------
 * @param string $name 变量 ， string def_val
 +----------------------------------------------------------
 * @return string
 +----------------------------------------------------------
 */
function P($name = '', $def_val = null) {
    static $_name = '';
    if (in_array($name, array(URL_MODULE, URL_ACTION))) {
        (isset($_POST[$name]) && $_POST[$name]) ? $_name = trim($_POST[$name]) : $_name = URL_DEFAULT_NAME;
    } else {
        isset($_POST[$name]) ? $_name = trim_deep($_POST[$name]) : $_name = $def_val;
    }
    return $_name;
}

/**
 +----------------------------------------------------------
 * 替代$_GET
 +----------------------------------------------------------
 * @param string $name 变量 ， string def_val
 +----------------------------------------------------------
 * @return string
 +----------------------------------------------------------
 */
function G($name = '', $def_val = null) {
    static $_name = '';
    if (in_array($name, array(URL_MODULE, URL_ACTION))) {
        (isset($_GET[$name]) && $_GET[$name]) ? $_name = trim($_GET[$name]) : $_name = URL_DEFAULT_NAME;
    } else {
        isset($_GET[$name]) ? $_name = trim_deep($_GET[$name]) : $_name = $def_val;
    }    
    return $_name;
}

/**
 +----------------------------------------------------------
 * 替代$_SESSION
 +----------------------------------------------------------
 * @param string $name 变量
 * @param string $value 变量值
 +----------------------------------------------------------
 * @return mixed
 +----------------------------------------------------------
 */
function S($name = '', $value = null) {
    if (!empty($name) && !is_null($value)) {
        $_SESSION[strtoupper($name)] = $value;
    }

    if (!empty($name) && isset($_SESSION[strtoupper($name)])) {
        return $_SESSION[strtoupper($name)];
    }

    return;
}

/**
 +----------------------------------------------------------
 * 获取环境配置值
 +----------------------------------------------------------
 * @param string $name 变量
 * @param string $value 变量值
 +----------------------------------------------------------
 * @return mixed
 +----------------------------------------------------------
 */
function C($name = '', $value = null) {
    static $_config = array();

    if (!empty($name) && !is_null($value)) {
        $_config[strtoupper($name)] = $value;
    }

    // 缓存全部配置值
    if (is_array($name)) {
        $_config = array_merge($_config, array_change_key_case($name, CASE_UPPER));
        return $_config;
    }

    if (!empty($name) && isset($_config[strtoupper($name)])) {
        return $_config[strtoupper($name)];
    }

    if ($name && !isset($_config[strtoupper($name)])) {
        return '';
    }

    return $_config;
}

/**
 +----------------------------------------------------------
 * 快速PHP文件数据读取和保存
 * 针对简单类型数据 字符串、数组
 +----------------------------------------------------------
 * @param string $name 变量
 * @param string $value 变量值
 * @param int    $expire 生存周期
 * @param string $path 路径
 +----------------------------------------------------------
 * @return mixed
 +----------------------------------------------------------
 */
function F($name, $value = '', $expire = -1, $path = '') {
    static $_cache = array();
    $filename = $path . $name . '.php';

    if ('' !== $value) {
        if (is_null($value)) {
            // 删除缓存
            $result = unlink($filename);
            if ($result) {
                unset($_cache[$name]);
            }
            return $result;
        } else {
            // 缓存数据
            $content = safeSTR($value);
            $result = file_put_contents($filename, $content);
            $_cache[$name] = $value;
        }
        return;
    }

    if (isset($_cache[$name])) {
        return $_cache[$name];
    }

    // 获取缓存数据
    if (is_file($filename) && false !== $content = file_get_contents($filename)) {
        $expire = (int) substr($content, 44, 12);
        if ($expire != -1 && time() > filemtime($filename) + $expire) {
            //缓存过期删除缓存文件
            unlink($filename);
            return false;
        }
        $value = eval(substr($content, 57, -2));
        $_cache[$name] = $value;
    } else {
        $value = false;
    }
    return $value;
}

/**
 +----------------------------------------------------------
 * 是否ajax提交
 +----------------------------------------------------------
 * @return Boolean
 +----------------------------------------------------------
 */
function isAjax() {
    $jsoncallback = G('jsoncallback');
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) ? $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest' : (empty($jsoncallback) ? false : true);
}

/**
 +----------------------------------------------------------
 * 是否支持MemCache
 +----------------------------------------------------------
 * @return Boolean
 +----------------------------------------------------------
 */
function isMemCache() {
    return class_exists('Memcache', false);
}

/**
 +----------------------------------------------------------
 * 自动输入过滤
 +----------------------------------------------------------
 * @param object  $parse 对象名
 +----------------------------------------------------------
 * @return object
 +----------------------------------------------------------
 */
function autoFilter(&$parse) {
    if (is_array($parse)) {
        $parse = array_map(__FUNCTION__, $parse);
    } else {
        $parse = safeSTR($parse);
    }

    return $parse;
}

/**
 +----------------------------------------------------------
 * 实现combine
 +----------------------------------------------------------
 * @param array  $keys   数组1
 * @param array  $values 数组2
 +----------------------------------------------------------
 * @return array
 +----------------------------------------------------------
 */
if (!function_exists('array_combine')) {

    function array_combine($keys, $values) {
        $keys = array_values($keys);
        $values = array_values($values);
        $combined = array();
        $num = count($values);
        foreach (range(0, $num - 1) as $i) {
            $combined[$keys[$i]] = $values[$i];
        }
        return $combined;
    }

}

/**
 +----------------------------------------------------------
 * 去掉指定HTML标记
 +----------------------------------------------------------
 * @param string  $text 字串
 * @param array|string  $tags 需过滤掉标识名
 +----------------------------------------------------------
 * @return string
 +----------------------------------------------------------
 */
function uStripTags($text, $tags = array()) {
    $args = func_get_args();
    $text = array_shift($args);
    $tags = func_num_args() > 2 ? array_diff($args, array($text)) : (array) $tags;
    foreach ($tags as $tag) {
        if (preg_match_all('/<' . $tag . '[^>]*>(.*)<\/' . $tag . '>|<' . $tag . '[^>]*>/iU', $text, $found)) {
            $text = str_replace($found[0], $found[1], $text);
        }
    }
    return $text;
}

/**
 +----------------------------------------------------------
 * 去掉所有HTML标记,除 <strong>, <em>, <u> and <a>
 +----------------------------------------------------------
 * @param string  $html 变量
 +----------------------------------------------------------
 * @return string
 +----------------------------------------------------------
 */
function safeHTML($html, $allowable = '<strong><em><i><u><a><br>') {
    $html = strip_tags($html, $allowable);
    $html = str_replace('<a ', '<a rel="nofollow" ', $html);
    $html = str_replace('\t', '', $html);
    $html = str_replace('\r\n', '', $html);
    $html = str_replace('\r', '', $html);
    $html = str_replace('\n', '', $html);
    $html = str_replace(chr(10), '', $html);
    $html = str_replace(chr(13), '', $html);
    return $html;
}

/**
 +----------------------------------------------------------
 * SQL字符串入库过滤
 +----------------------------------------------------------
 * @param string  $string 变量
 +----------------------------------------------------------
 * @return string
 +----------------------------------------------------------
 */
function safeSQL($string) {
    $str = '';
    $length = strlen($string);
    for ($i = 0; $i < $length; $i++) {
        $char = $string[$i];
        switch ($char) {
            case "'":
                $str .= "\'";
                break;

            case "\\":
                $str .= "\\\\";
                break;

            case "\n":
                $str .= "\\n";
                break;

            case "\r":
                $str .= "\\r";
                break;

            default:
                $str .= $char;
        }
    }
    return $str;
}

/**
 +----------------------------------------------------------
 * 字符串手动过滤
 +----------------------------------------------------------
 * @param string  $string 变量
 * @param string  $allowable_tags 忽略HTML
 +----------------------------------------------------------
 * @return string
 +----------------------------------------------------------
 */
function safeSTR($string, $allowable_tags=null) {
    $string = trim($string);       // 清理空格字符
    
    $string = str_replace(chr(10), '<br>', $string);
    $string = str_replace(chr(13), '<br>', $string);   
    
    $string = safeVALUE($string);  // 字符转义
    $string = nl2br($string);               // 将换行符转化为<br />
    $string = strip_tags($string, $allowable_tags);          // 过滤文本中的HTML标签
    $string = htmlspecialchars($string);    // 将文本中的内容转换为HTML实体
    return $string;
}

/**
 +----------------------------------------------------------
 * 初步安全过滤
 +----------------------------------------------------------
 * @param string  $string 变量
 +----------------------------------------------------------
 * @return string
 +----------------------------------------------------------
 */
function safeVALUE($string) {
    if (is_array($string)) {
        $string = array_map(__FUNCTION__, $string);
    } else {
        $string = trim($string);

        if (get_magic_quotes_gpc())
            $string = stripslashes($string);

        if (is_numeric($string) && ceil($string) == $string && intval($string) == $string && substr($string, 0, 1) !== '0') {
            $string = intval($string);
        } else {
            $string = addslashes($string);
        }
    }

    return $string;
}

/**
 +----------------------------------------------------------
 * 字串入库处理
 +----------------------------------------------------------
 * @param string  $content 变量 内容
 +----------------------------------------------------------
 * @return string
 +----------------------------------------------------------
 */
function quotes($content) {
    if (!get_magic_quotes_gpc()) {
        $content = is_array($content) ? array_map(__FUNCTION__, $content) : addslashes($content);
    }
    return $content;
}

/**
 +----------------------------------------------------------
 * 字串出库处理
 +----------------------------------------------------------
 * @param string  $content 变量 内容
 +----------------------------------------------------------
 * @return string
 +----------------------------------------------------------
 */
function unquotes($content) {
    $content = is_array($content) ? array_map(__FUNCTION__, $content) : htmlspecialchars_decode(stripslashes($content));
    return $content;
}

/**
 +----------------------------------------------------------
 * 创建文件夹
 +----------------------------------------------------------
 * @param string  $path 变量
 * @param string  $mode 权限值
 +----------------------------------------------------------
 * @return boolean
 +----------------------------------------------------------
 */
function mk_dir($path, $mode = 0777) {
    if (empty($path)) {
        $_rtn = false;
    } elseif (!is_dir($path)) {

        $_rtn = mkdir($path, $mode, true);
        if (!chmod($path, $mode)) {
            //echo "<script>alert('给目录:{$path}\r\n赋权限失败!');</script>";
            //$_rtn = "给目录[{$path}]赋权限失败!";
            $_rtn = false;
        }
    } else {
        $_rtn = false;
    }
    return $_rtn;
}

/**
 +----------------------------------------------------------
 * 创建文件
 +----------------------------------------------------------
 * @param string  $filepath 文件路径
 * @param string  $content 文件内容
 * @param string  $mode 权限值
 +----------------------------------------------------------
 * @return boolean
 +----------------------------------------------------------
 */
function mk_file($filepath, $content = '', $mode = 0777) {
    if (empty($filepath)) {
        $_rtn = false;
    } else {
        $_rtn = is_file($filepath);
        if ($_rtn && !chmod($filepath, $mode)) {
            echo "<script>alert('给文件:{$filepath} 赋权限[{$mode}]失败!');</script>";
        }
        $_tmp = file_put_contents($filepath, $content);
    }
    return $_rtn;
}

/**
 +----------------------------------------------------------
 * 判断目录是否为空
 +----------------------------------------------------------
 * @param string $directory 目录路径
 +----------------------------------------------------------
 * @return boolean
 +----------------------------------------------------------
 */
function empty_dir($directory) {
    if (!is_dir($directory) || !$directory) {
        return false;
    }

    $handle = opendir($directory);
    while (($file = readdir($handle)) !== false) {
        if ($file != '.' && $file != '..') {
            closedir($handle);
            return false;
        }
    }
    closedir($handle);
    return true;
}

/**
 +----------------------------------------------------------
 * 删除目录及其下所有文件
 +----------------------------------------------------------
 * @param string  $dirName 变量
 +----------------------------------------------------------
 * @return boolean
 +----------------------------------------------------------
 */
function remove_dir($dirName) {
    $result = false;
    if (!is_dir($dirName))
        return $result; // 目录名称错误

    $handle = opendir($dirName);
    while (($file = readdir($handle)) !== false) {
        if ($file != '.' && $file != '..') {
            $dir = $dirName . DS . $file;
            is_dir($dir) ? remove_dir($dir) : unlink($dir);
        }
    }

    closedir($handle);
    $result = rmdir($dirName) ? true : false;
    return $result;
}

/**
 +----------------------------------------------------------
 * URL重定向
 +----------------------------------------------------------
 * @static
 * @access public
 +----------------------------------------------------------
 * @param string $url  要定向的URL地址
 * @param integer $time  定向的延迟时间，单位为秒
 * @param string $msg  提示信息
 +----------------------------------------------------------
 */
function redirect($url, $time = 0, $msg = '') {
    $url = str_replace(array('\n', '\r'), '', $url);
    $msg = empty($msg) ? "系统将在{$time}秒之后自动跳转到{$url}！" : $msg;

    if (!Headers_sent()) {
        Header('Content-Type:text/html; charset=' . C('OUTPUT_CHARSET'));
        if (0 === $time) {
            Header('Location: ' . $url);
        } else {
            Header("refresh:{$time};url={$url}");
            echo($msg);
        }
        exit(0);
    }

    $str = "<meta http-equiv='Refresh' content='{$time};URL={$url}' />";
    $time != 0 ? $str .= $msg : '';
    exit($str);
}

/**
 +----------------------------------------------------------
 * 带提示信息转向
 +----------------------------------------------------------
 * @param string  $url 变量 跳转地址
 * @param string  $msg 变量 提示消息
 * @param void  $iframe 变量 需要刷新的窗口名
 * @param void  $istarget 变量 是否跳转去指定窗口打开地址
 +----------------------------------------------------------
 * @return void
 +----------------------------------------------------------
 */
function gourl($url, $msg = '', $iframe = false, $istarget = false) {
    $istar = boolVal($istarget);
    $m_str = $msg ? 'alert(\'' . $msg . '\');' : '';

    if ($iframe) {
        $u_str = $istar ? 'window.parent[\'' . $iframe . '\'].location.href=\'' . $url . '\';' : 'window.parent[\'' . $iframe . '\'].location.reload();' . 'location.href=\'' . $url . '\';';
    } else if ($iframe && 'parent' == strtolower($iframe)) {
        $u_str = $istar ? 'parent.location.href=\'' . $url . '\';' : 'parent.location.reload();';
    } else if ($url) {
        $u_str = 'location.href=\'' . $url . '\';';
    } else {
        $u_str = '';
    }

    // 优先用header跳转
    if (!Headers_sent() && !$iframe && !$m_str && $url)
        Header("location:{$url}");

    // JS跳转
    exit('<script>' . $m_str . $u_str . '</script>');
}

/**
 +----------------------------------------------------------
 * 跳转至新窗口
 +----------------------------------------------------------
 * @param string  $url 变量 跳转地址
 +----------------------------------------------------------
 * @return void
 +----------------------------------------------------------
 */
function open($url) {
    $str = '<script>';
    $str .= "var wintip = window.top.open('','win" . rand_string(12) . "');";
    $str .= "wintip.location.href = '{$url}';";
    $str .= 'wintip.focus();';
    $str .= '</script>';
    exit($str);
}

/**
 +----------------------------------------------------------
 * 判断转向
 +----------------------------------------------------------
 * @param string  $url_1 变量 跳转地址
 * @param string  $url_2 变量 跳转地址
 * @param string  $msg 变量 提示内容
 +----------------------------------------------------------
 * @return void
 +----------------------------------------------------------
 */
function confirm($msg, $url_1, $url_2) {
    if (!$msg || !$url_1 || !$url_2)
        return false;
    $str = "<script>\n";
    $str .= "if (confirm('" . $msg . "')) {";
    $str .= "  window.location.href='" . $url_1 . "';";
    $str .= '} else {';
    $str .= "  window.location.href='" . $url_2 . "';";
    $str .= "};\n";
    $str .= '</script>';
    exit($str);
}

/**
 +----------------------------------------------------------
 * 转向顶层
 +----------------------------------------------------------
 * @param string  $url 变量 跳转地址
 * @param string  $msg 变量 提示内容
 +----------------------------------------------------------
 * @return void
 +----------------------------------------------------------
 */
function gourltop($url, $msg = null) {
    $str = '<script>';
    if (!empty($msg))
        $str .= 'alert(\'' . $msg . '\');';
    if ($url)
        $str .= 'window:top.location=\'' . $url . '\';';
    $str .= '</script>';
    exit($str);
}

/**
 +----------------------------------------------------------
 * 带提示返回
 +----------------------------------------------------------
 * @param string  $msg 变量 内容
 +----------------------------------------------------------
 * @return void
 +----------------------------------------------------------
 */
function goback($msg = null) {
    $Str = '<script>';

    if ($msg != null)
        $Str .= "alert('" . $msg . "');";

    $Str .= 'history.back();';
    $Str .= '</script>';
    exit($Str);
}

/**
 +----------------------------------------------------------
 * XML转成数组
 +----------------------------------------------------------
 * @param string  $xml 变量 xml内容 || xml文件路径
 +----------------------------------------------------------
 * @return array
 +----------------------------------------------------------
 */
function xmlToArray($xml, $section = '') {
    if (!$xml)
        return array();

    if (strstr($xml, '<?xml')) {
        $xml = simplexml_load_string($xml);
    } else if (is_file($xml)) {
        $xml = simplexml_load_file($xml);
    } else {
        return array();
    }

    if ($section && isset($xml->$section))
        $xml = $xml->$section;

    return (array)$xml;
}
function pregXmlToArray($xml) {
    $reg = "/<(\\w+)[^>]*?>([\\x00-\\xFF]*?)<\\/\\1>/";
    if (is_file($xml)) $xml = file_get_contents($xml);    
    if (preg_match_all($reg, $xml, $matches))
    {
        $count = count($matches[0]);
        $arr = array();
        for ($i = 0; $i < $count; $i++)
        {
            $key= $matches[1][$i];
            $val = pregXmlToArray($matches[2][$i]);  // 递归
            if (array_key_exists($key, $arr))
            {
                if (is_array($arr[$key]))
                {
                    if (!array_key_exists(0, $arr[$key]))
                    {
                        $arr[$key] = array($arr[$key]);
                    }
                } else {
                    $arr[$key] = array($arr[$key]);
                }
                $arr[$key][] = $val;
            }else{
                $arr[$key] = $val;
            }
        }
        return $arr;
    } else {
        return $xml;
    }
}

/**
 +----------------------------------------------------------
 * array转成xml格式字符串
 +----------------------------------------------------------
 * @param array  $data 数组
 * @param array  $encoding xml编码 utf-8 || gb2312
 * @param array  $root 根节点名称
 * @param array  $st 格式 default || other
 +----------------------------------------------------------
 * @return string
 +----------------------------------------------------------
 */
function arrayToXml($data, $encoding = 'utf-8', $root = 'root', $st = 'default') {
    $xml = '<?xml version="1.0" encoding="' . $encoding . '" ?>';
    $xml .= '<' . $root . '>';

    if (!in_array(strtolower($st), array('default', 'other')))
        $st = 'default';

    $_method = 'dataBy' . ucfirst($st);

    if (isset($data[0])) {
        foreach ($data as $v)
            $xml .= $_method($v);
    } else {
        $xml .= $_method($data);
    }

    $xml .= '</' . $root . '>';
    return $xml;
}

function dataByOther($data) {
    if (is_object($data))
        $data = get_object_vars($data);
    if ($data && !is_array($data))
        $data = (array) $data;
    $xml = '';

    foreach ($data as $key => $val) {
        if (is_array($val) || is_object($val)) {
            is_object($val) ? $val = get_object_vars($val) : '';
            $xmls = '';
            foreach ($val as $k => $v)
                $xmls .= "$k=\"$v\" ";
            $xml .= "<$key $xmls/>";
        } else {
            $xml .= "<$key>" . (!is_numeric($val) ? "<![CDATA[" . $val . "]]>" : $val) . "</$key>";
        }
    }
    return $xml;
}

function dataByDefault($data) {
    if (is_object($data))
        $data = get_object_vars($data);
    if ($data && !is_array($data))
        $data = (array) $data;
    $xml = '';

    foreach ($data as $key => $val) {
        is_numeric($key) && $key = "item id=\"$key\"";
        $xml .= "<$key>";
        $xml .= ( is_array($val) || is_object($val)) ? dataByDefault($val) : (!is_numeric($val) ? "<![CDATA[" . $val . "]]>" : $val);
        list($key, ) = explode(' ', $key);
        $xml .= "</$key>";
    }

    return $xml;
}

/**
 +----------------------------------------------------------
 * object 转成数组
 +----------------------------------------------------------
 * @param string  $object 对象
 +----------------------------------------------------------
 * @return array
 +----------------------------------------------------------
 */
function object_toArray($object) {
    $rtn = array();
    if (is_array($object)) {
        foreach ($object as $key => $value) {
            $rtn[$key] = object_toArray($value);
        }
    } else {
        if (empty($object)) {
            if (is_numeric($object) && (intval($object) == 0 || $object == null))
                return $object;
            return $rtn;
        }
        if (!is_object($object))
            return $object;
        $var = get_object_vars($object);

        if ($var) {
            foreach ($var as $key => $value) {
                $rtn[$key] = object_toArray($value);
            }
        } else {
            return $var;
        }
    }
    return $rtn;
}

/**
 +----------------------------------------------------------
 * 取客户端IP
 +----------------------------------------------------------
 * @return string
 +----------------------------------------------------------
 */
function getClientIp() {
    if (getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), 'unknown')) {
        $ip = getenv('HTTP_CLIENT_IP');
    } else if (getenv('HTTP_X_FORWARDED_FOR') && strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), 'unknown')) {
        $ip = getenv('HTTP_X_FORWARDED_FOR');
    } else if (getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), 'unknown')) {
        $ip = getenv('REMOTE_ADDR');
    } else if (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], 'unknown')) {
        $ip = $_SERVER['REMOTE_ADDR'];
    } else {
        $ip = 'unknown';
    }
    return $ip;
}

/**
 +----------------------------------------------------------
 * 取服务端HOST
 +----------------------------------------------------------
 * @return string
 +----------------------------------------------------------
 */
function getHost() {
    return isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
}


/**
 +----------------------------------------------------------
 * 取访问来路HOST
 +----------------------------------------------------------
 * @return string
 +----------------------------------------------------------
 */
function getRefererHost() {
    return isset($_SERVER['HTTP_X_FORWARDED_HOST']) ? $_SERVER['HTTP_X_FORWARDED_HOST'] : '';
}

/**
 +----------------------------------------------------------
 * 字符串TO数组
 +----------------------------------------------------------
 * @param string  $str 字串
 * eg:
 * '456f2,2010-11-01,【子女】热心贴！大家一起养孩子！,
 * dcfd9,2010-11-04,王佳伟 专访主贴 还原一个最真实的人物传奇'
 +----------------------------------------------------------
 * @param string  $mode 模式 当值大于1时规则$wh生效
 * @param string  $wh 规则 比较符|截取条件|数组拼装元素
 * eg: 0|3|0, 1|3|1
 * 0表示===, 1表示!==
 * 3表示条件为能被3整除时的元素做为新数组的key值,
 * 0表示新数组取除key值外的所有元素, 1表示截取后的最后一个元素放入新数组
 * @return array
 +----------------------------------------------------------
 */
function string_toArray($str, $sp = ',', $mode = 0, $wh = '') {
    if ($str && $sp && true) {
        if ($mode) {
            if (empty($wh) || substr_count($wh, '|') < 2)
                $wh = '0|0|0';

            list($w, $s, $p) = explode('|', $wh);
            $mode = intval($mode);
            $ex = array('===', '!==');
            $ex_str = empty($ex[$w]) ? $ex[0] : $ex[$w];
            $s_str = intval($s) < 2 ? 2 : intval($s);
            $exp = array('', '$m = $k%' . $s_str . ' ' . $ex_str . ' 0;');
            $p = intval($p) >= $s_str ? $s_str - 1 : intval($p);
        }

        if (empty($mode) || empty($exp[$mode]))
            return explode($sp, $str);

        foreach ($tmp_parent = explode($sp, $str) as $k => $val) {
            eval($exp[$mode]);
            if ($m && intval($p) > 0) {
                $tmp[] = $val;
                foreach (range(1, intval($p)) as $kk) {
                    if (isset($tmp_parent[$k + $kk]))
                        $tmp_v[] = $tmp_parent[$k + $kk];
                }

                $tmp_val[] = $tmp_v;
                unset($tmp_v);
            } elseif ($m) {
                $tmp[] = $val;

                if ($s_str > 2) {
                    foreach (range(1, intval($s_str - 1)) as $kk) {
                        if (isset($tmp_parent[$k + $kk]))
                            $tmp_v[] = $tmp_parent[$k + $kk];
                    }
                }

                $tmp_val[] = isset($tmp_v) ? $tmp_v : $tmp_parent[$k + 1];
                unset($tmp_v);
            }
        }

        if (isset($tmp) && is_array($tmp) && isset($tmp_val) && is_array($tmp_val))
            $tmp = array_combine($tmp, $tmp_val);
        unset($tmp_parent);
    }
    return isset($tmp) ? $tmp : array();
}

/**
 +----------------------------------------------------------
 * 多维数组TO字符串
 +----------------------------------------------------------
 * @return string
 +----------------------------------------------------------
 */
function array_toString($arr) {
    if (is_object($arr) || is_array($arr)) {
        return implode(',', array_map(__FUNCTION__, is_object($arr) ? object_toArray($arr) : $arr));
    }
    return $arr;
}

/**
 +----------------------------------------------------------
 * 二维数组TO单一数组
 +----------------------------------------------------------
 * @return array
 +----------------------------------------------------------
 */
function array_2toSingle($arr) {
    foreach ($arr as $key => $val) {
        $arr[$key] = implode(',', $val);
    }
    return $arr;
}

/**
 +----------------------------------------------------------
 * ob_start 缓存输出处理函数
 +----------------------------------------------------------
 * @return string
 +----------------------------------------------------------
 */
function OBOutBuffer() {
    $buff = ob_get_clean();

    // 输出调试信息
    $_strtmp = '';

    if (C('DEBUG_MODE') && count(PLFException::$_SqlStr)) {
        $_strtmp = '
            <div style="padding:10px;margin:5px;color:#666;background:#FCFCFC;border:1px solid #E0E0E0;">
                <p>[ SQL调试信息 ]</p>
                <p style="background:#E7F7FF;border:1px solid #E0E0E0;color:#535353;">SQL语句：' . nl2br(implode('SQL语句：', PLFException::$_SqlStr)) . '</p></div>
                <div style="padding:10px;margin:5px;color:#666;background:#FCFCFC;border:1px solid #E0E0E0;">
                <p>[ 页面耗时:' . get_runtime(BEGINTIME) . 's ]</p>
                <p>[ 基类加载:' . get_runtime(array(CLASSLOADTIMEBEG, CLASSLOADTIMEEND)) . 's ]</p>
            </div>
        ';
    }

    $buff = stripos($buff, '</body>') ? str_replace(substr($buff, stripos($buff, '</body>'), 7), '</body>', $buff) : $buff;
    return auto_charset(strtr($buff, array('</body>' => $_strtmp . '</body>')), C('TEMPLATE_CHARSET'), C('OUTPUT_CHARSET'));
}

/**
 +----------------------------------------------------------
 * bool值处理函数
 +----------------------------------------------------------
 * @return boolean
 +----------------------------------------------------------
 */
if (!function_exists('boolVal')) {
    function boolVal($var) {
        $b_var = false;
        if (is_bool($var)) {
            $b_var = $var;
        } else if (in_array($var, array('false', 'False', 'FALSE', 'no', 'No', 'n', 'N', '0', 'off', 'Off', 'OFF', 'NULL', 'null', false, 0, null, NULL), true)) {
            $b_var = false;
        } else if (in_array($var, array('true', 'True', 'TRUE', 'yes', 'Yes', 'y', 'Y', '1', 'on', 'On', 'ON', true, 1), true)) {
            $b_var = true;
        } else {
            $b_var = $var && true ? true : false;
        }
        return $b_var;
    }
}

/**
 +----------------------------------------------------------
 * 写文件
 +----------------------------------------------------------
 * @return bool
 +----------------------------------------------------------
 */
function filePutTxt($file = '', $conts = '') {
    if ($conts) {
        clearstatcache();

        if (is_file($file) && filesize($file) >= C('LOG_FILE_SIZE')) {
            list($name, $ext) = explode('.', $file, 2);
            $file = $name . '_' . time() . '.' . $ext;
        } else if (is_file($file)) {
            $conts_tmp = file($file);
        }

        list($conts_tmp[]) = explode(';', $conts);
        return file_put_contents($file, implode(PHP_EOL, $conts_tmp), LOCK_EX);
    }

    return false;
}

/**
 +----------------------------------------------------------
 * 判断页面来路是否为授权来路
 +----------------------------------------------------------
 * @param array  $dec_url 授权来路
 +----------------------------------------------------------
 * @return  bool
 +----------------------------------------------------------
 */
function referer($dec_url = array()) {
    $curr_url = isset($_SERVER['HTTP_REFERER']) ? parse_url($_SERVER['HTTP_REFERER']) : array();
    if (isset($curr_url['host']) && is_array($dec_url))
        return in_array($curr_url['host'], $dec_url);
    return false;
}

/**
 +----------------------------------------------------------
 * 转成半角字符
 +----------------------------------------------------------
 * @param array  $str 字符串
 +----------------------------------------------------------
 * @return  bool
 +----------------------------------------------------------
 */
function toSemiangle($str) {
    $arr = array(
        '０' => '0', '１' => '1', '２' => '2', '３' => '3', '４' => '4',
        '５' => '5', '６' => '6', '７' => '7', '８' => '8', '９' => '9',
        'Ａ' => 'A', 'Ｂ' => 'B', 'Ｃ' => 'C', 'Ｄ' => 'D', 'Ｅ' => 'E',
        'Ｆ' => 'F', 'Ｇ' => 'G', 'Ｈ' => 'H', 'Ｉ' => 'I', 'Ｊ' => 'J',
        'Ｋ' => 'K', 'Ｌ' => 'L', 'Ｍ' => 'M', 'Ｎ' => 'N', 'Ｏ' => 'O',
        'Ｐ' => 'P', 'Ｑ' => 'Q', 'Ｒ' => 'R', 'Ｓ' => 'S', 'Ｔ' => 'T',
        'Ｕ' => 'U', 'Ｖ' => 'V', 'Ｗ' => 'W', 'Ｘ' => 'X', 'Ｙ' => 'Y',
        'Ｚ' => 'Z', 'ａ' => 'a', 'ｂ' => 'b', 'ｃ' => 'c', 'ｄ' => 'd',
        'ｅ' => 'e', 'ｆ' => 'f', 'ｇ' => 'g', 'ｈ' => 'h', 'ｉ' => 'i',
        'ｊ' => 'j', 'ｋ' => 'k', 'ｌ' => 'l', 'ｍ' => 'm', 'ｎ' => 'n',
        'ｏ' => 'o', 'ｐ' => 'p', 'ｑ' => 'q', 'ｒ' => 'r', 'ｓ' => 's',
        'ｔ' => 't', 'ｕ' => 'u', 'ｖ' => 'v', 'ｗ' => 'w', 'ｘ' => 'x',
        'ｙ' => 'y', 'ｚ' => 'z', '（' => '(', '）' => ')', '〔' => '[',
        '〕' => ']', '【' => '[', '】' => ']', '〖' => '[', '〗' => ']',
        '“' => '[', '”' => ']', '‘' => '[', '’' => ']', '｛' => '{',
        '｝' => '}', '《' => '<', '》' => '>', '％' => '%', '＋' => '+',
        '—' => '-', '－' => '-', '～' => '-', '：' => ':', '。' => '.',
        '、' => ',', '，' => '.', '、' => '.', '；' => ',', '？' => '?',
        '！' => '!', '…' => '-', '‖' => '|', '”' => '"', '’' => '`',
        '‘' => '`', '｜' => '|', '〃' => '"', '　' => ' '
    );
    return strtr($str, $arr);
}

/**
 +----------------------------------------------------------
 * 指定内容内关键字替换
 +----------------------------------------------------------
 * @param array   $_keys_array 键字映射表
 * @param string  $content     内容
 +----------------------------------------------------------
 * @return  string
 +----------------------------------------------------------
 */
function replaceKeywords($_keys_array, $content) {
    if (count($_keys_array) > 0) {
        preg_match_all("/(<a.*?>.*?<\/a>)|(<img[^<>]+>)/i", $content, $out);
        $_cnts = count($out[0]);
        $range = $_cnts > 0 ? range(0, $_cnts - 1) : array();

        foreach ($range as $i) {
            $content = str_replace($out[0][$i], "#{$i}#", $content);
        }

        $content = strtr($content, $_keys_array);

        foreach ($range as $i) {
            $content = str_replace("#{$i}#", $out[0][$i], $content);
        }
    }
    return $content;
}

/**
 +----------------------------------------------------------
 * 遍历文件夹及子目录下所有文件
 +----------------------------------------------------------
 * @param array|string  $path 目录路径
 * @param array|string  $type 后缀名
 * @param boolean  $is_Recursive  是否遍历子目录
 +----------------------------------------------------------
 * @return  array
 +----------------------------------------------------------
 */
function listFiles($path, $type = '.txt', $is_Recursive = true) {
    if (is_array($path)) {
        $pack = array_map(__FUNCTION__, $path, !is_array($type) ? array($type) : $type, array($is_Recursive));
    } elseif (is_dir($path) && ($dir = opendir($path))) {
        while (false !== ($file = readdir($dir))) {
            if (!in_array($file, array('.', '..', '.svn', 'RECYCLER', 'System Volume Information'))) {
                if (is_file($path . $file) && (preg_match('/\\' . (is_array($type) ? implode('|\\', $type) : $type) . '$/si', $file) > 0)) {
                    $pack[] = $path . $file;
                } elseif (is_dir($path . $file) && boolVal($is_Recursive)) {
                    $pack = array_map(__FUNCTION__, array($path . $file . DS), !is_array($type) ? array($type) : $type, array($is_Recursive));
                }
            }
        }

        closedir($dir);
    } elseif (is_file($path) && (preg_match('/\\' . (is_array($type) ? implode("|\\", $type) : $type) . '$/si', $path) > 0)) {
        $pack[] = $path;
    }

    return $pack;
}

/**
 +----------------------------------------------------------
 * 处理BMP格式图片
 +----------------------------------------------------------
 * @param string  $filename 目录路径
 +----------------------------------------------------------
 * @return image resource
 +----------------------------------------------------------
 */
if (!function_exists('imagecreatefrombmp')) {

    function imagecreatefrombmp($filename) {
        // version 1.00
        if (!($fh = fopen($filename, 'rb'))) {
            trigger_error('imagecreatefrombmp: Can not open ' . $filename, E_USER_WARNING);
            return false;
        }

        // read file header
        $meta = unpack('vtype/Vfilesize/Vreserved/Voffset', fread($fh, 14));

        // check for bitmap
        if ($meta['type'] != 19778) {
            trigger_error('imagecreatefrombmp: ' . $filename . ' is not a bitmap!', E_USER_WARNING);
            return false;
        }

        // read image header
        $meta += unpack('Vheadersize/Vwidth/Vheight/vplanes/vbits/Vcompression/Vimagesize/Vxres/Vyres/Vcolors/Vimportant', fread($fh, 40));

        // read additional 16bit header
        if ($meta['bits'] == 16)
            $meta += unpack('VrMask/VgMask/VbMask', fread($fh, 12));

        // set bytes and padding
        $meta['bytes'] = $meta['bits'] / 8;
        $meta['decal'] = 4 - (4 * (($meta['width'] * $meta['bytes'] / 4) - floor($meta['width'] * $meta['bytes'] / 4)));
        if ($meta['decal'] == 4)
            $meta['decal'] = 0;

        // obtain imagesize
        if ($meta['imagesize'] < 1) {
            $meta['imagesize'] = $meta['filesize'] - $meta['offset'];

            // in rare cases filesize is equal to offset so we need to read physical size
            if ($meta['imagesize'] < 1) {
                $meta['imagesize'] = @filesize($filename) - $meta['offset'];
                if ($meta['imagesize'] < 1) {
                    trigger_error('imagecreatefrombmp: Can not obtain filesize of ' . $filename . '!', E_USER_WARNING);
                    return false;
                }
            }
        }

        // calculate colors
        $meta['colors'] = !$meta['colors'] ? pow(2, $meta['bits']) : $meta['colors'];

        // read color palette
        $palette = array();
        if ($meta['bits'] < 16) {
            $palette = unpack('l' . $meta['colors'], fread($fh, $meta['colors'] * 4));

            // in rare cases the color value is signed
            if ($palette[1] < 0) {
                foreach ($palette as $i => $color) {
                    $palette[$i] = $color + 16777216;
                }
            }
        }

        // create gd image
        $im = imagecreatetruecolor($meta['width'], $meta['height']);
        $data = fread($fh, $meta['imagesize']);
        $p = 0;
        $vide = chr(0);
        $y = $meta['height'] - 1;
        $error = 'imagecreatefrombmp: ' . $filename . ' has not enough data!';

        // loop through the image data beginning with the lower left corner
        while ($y >= 0) {
            $x = 0;
            while ($x < $meta['width']) {
                switch ($meta['bits']) {
                    case 32:
                    case 24:
                        if (!($part = substr($data, $p, 3))) {
                            trigger_error($error, E_USER_WARNING);
                            return $im;
                        }
                        $color = unpack('V', $part . $vide);
                        break;
                    case 16:
                        if (!($part = substr($data, $p, 2))) {
                            trigger_error($error, E_USER_WARNING);
                            return $im;
                        }
                        $color = unpack('v', $part);
                        $color[1] = (($color[1] & 0xf800) >> 8) * 65536 + (($color[1] & 0x07e0) >> 3) * 256 + (($color[1] & 0x001f) << 3);
                        break;

                    case 8:
                        $color = unpack('n', $vide . substr($data, $p, 1));
                        $color[1] = $palette[$color[1] + 1];
                        break;
                    case 4:
                        $color = unpack('n', $vide . substr($data, floor($p), 1));
                        $color[1] = ($p * 2) % 2 == 0 ? $color[1] >> 4 : $color[1] & 0x0F;
                        $color[1] = $palette[$color[1] + 1];
                        break;
                    case 1:
                        $color = unpack('n', $vide . substr($data, floor($p), 1));
                        switch (($p * 8) % 8) {
                            case 0:
                                $color[1] = $color[1] >> 7;
                                break;
                            case 1:
                                $color[1] = ($color[1] & 0x40) >> 6;
                                break;
                            case 2:
                                $color[1] = ($color[1] & 0x20) >> 5;
                                break;
                            case 3:
                                $color[1] = ($color[1] & 0x10) >> 4;
                                break;
                            case 4:
                                $color[1] = ($color[1] & 0x8) >> 3;
                                break;
                            case 5:
                                $color[1] = ($color[1] & 0x4) >> 2;
                                break;
                            case 6:
                                $color[1] = ($color[1] & 0x2) >> 1;
                                break;
                            case 7:
                                $color[1] = ($color[1] & 0x1);
                                break;
                        }
                        $color[1] = $palette[$color[1] + 1];
                        break;
                    default:
                        trigger_error('imagecreatefrombmp: ' . $filename . 'has ' . $meta['bits'] . ' bitsand this is not supported!', E_USER_WARNING);
                        return false;
                }
                imagesetpixel($im, $x, $y, $color[1]);
                $x++;
                $p += $meta['bytes'];
            }
            $y--;
            $p += $meta['decal'];
        }

        fclose($fh);
        return $im;
    }

}

/**
 +----------------------------------------------------------
 * 获取文件类型
 +----------------------------------------------------------
 * @param string  $filename 文件路径
 +----------------------------------------------------------
 * @return string
 +----------------------------------------------------------
 */
if (!function_exists('mime_content_type')) {

    function mime_content_type($filename) {
        $mime_types = array(
            'txt' => 'text/plain',
            'htm' => 'text/html',
            'html' => 'text/html',
            'php' => 'text/html',
            'css' => 'text/css',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'xml' => 'application/xml',
            'swf' => 'application/x-shockwave-flash',
            'flv' => 'video/x-flv',
            // images
            'png' => 'image/png',
            'jpe' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpeg',
            'gif' => 'image/gif',
            'bmp' => 'image/bmp',
            'ico' => 'image/vnd.microsoft.icon',
            'tiff' => 'image/tiff',
            'tif' => 'image/tiff',
            'svg' => 'image/svg+xml',
            'svgz' => 'image/svg+xml',
            // archives
            'zip' => 'application/zip',
            'rar' => 'application/x-rar-compressed',
            'exe' => 'application/x-msdownload',
            'msi' => 'application/x-msdownload',
            'cab' => 'application/vnd.ms-cab-compressed',
            // audio/video
            'mp3' => 'audio/mpeg',
            'qt' => 'video/quicktime',
            'mov' => 'video/quicktime',
            // adobe
            'pdf' => 'application/pdf',
            'psd' => 'image/vnd.adobe.photoshop',
            'ai' => 'application/postscript',
            'eps' => 'application/postscript',
            'ps' => 'application/postscript',
            // ms office
            'doc' => 'application/msword',
            'rtf' => 'application/rtf',
            'xls' => 'application/vnd.ms-excel',
            'ppt' => 'application/vnd.ms-powerpoint',
            // open office
            'odt' => 'application/vnd.oasis.opendocument.text',
            'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
        );

        $ext = strtolower(array_pop(explode('.', $filename)));
        if (array_key_exists($ext, $mime_types)) {
            return $mime_types[$ext];
        } elseif (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME);
            $mimetype = finfo_file($finfo, $filename);
            finfo_close($finfo);
            return $mimetype;
        } else {
            return 'application/octet-stream';
        }
    }

}

/**
 +----------------------------------------------------------
 * 检查日期是否正确(出自PHP手册)
 +----------------------------------------------------------
 * @param string  $date 日期字串
 * @param string  $format 日期验证格式
 * @param string  $yearepsilon 年限上限
 +----------------------------------------------------------
 * @return array ['year', 'month', 'day']
 +----------------------------------------------------------
 */
function dateCheck($date, $format = 'ymd', $yearepsilon = 5000) {
    $date = str_replace('/', '-', $date);
    $format = strtolower($format);
    if (count($datebits = explode('-', $date)) != 3)
        return false;

    $year = intval($datebits[strpos($format, 'y')]);
    $month = intval($datebits[strpos($format, 'm')]);
    $day = intval($datebits[strpos($format, 'd')]);

    if ((abs($year - date('Y')) > $yearepsilon) ||
            ($month < 1) || ($month > 12) || ($day < 1) ||
            (($month == 2) && ($day > 28 + (!($year % 4)) - (!($year % 100)) + (!($year % 400)))) ||
            ($day > 30 + (($month > 7) ^ ($month & 1))))
        return false;

    return array('year' => $year, 'month' => $month, 'day' => $day);
}

/**
 +----------------------------------------------------------
 * INI文件写入(源自PHP手册)
 +----------------------------------------------------------
 * @param string  $file 文件路径
 * @param array  $data 写入数据
 * @param int  $i 空格数
 +----------------------------------------------------------
 * @return viod
 +----------------------------------------------------------
 */
function put_ini_file($file, $data, $i = 0) {
    $str = '';
    foreach ($data as $k => $v) {
        if (is_array($v)) {
            $str .= str_repeat(' ', $i*2) . "[$k]" . PHP_EOL;
            $str .= put_ini_file('', $v, $i+1);
        } else {
            $str .= str_repeat(' ', $i*2) . "$k = '$v'" . PHP_EOL;
        }
    }
    
    if ($file) return file_put_contents($file, $str);
    return $str;
}
?>