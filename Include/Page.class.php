<?php

/**
 +------------------------------------------------------------------------------
 * 分页功能
 +------------------------------------------------------------------------------
 * @category PLFrame
 * @package  Include
 * @author   Revised by pengzl<pengzl_gz@163.com>
 * @version  $Id: Page.class.php 2 2015-11-02 02:28:48Z pengzl $
 +------------------------------------------------------------------------------
 */
class Page {

    /**
     * config, public
     */
    public $page_name = 'pg'; // page标签，用来控制url页。比如说xxx.php?pg=2中的pg
    public $next_page = '>'; // 下一页
    public $pre_page = '<'; // 上一页
    public $first_page = 'First'; // 首页
    public $last_page = 'Last'; // 尾页
    public $pre_bar = '<<'; // 上一分页条
    public $next_bar = '>>'; // 下一分页条
    public $tag_html = 'span'; // html 标记
    public $format_left = '['; // 左格式符
    public $format_right = ']'; // 右格式符
    public $is_ajax = false; // 是否支持AJAX分页模式 
    public $is_format = true; // 是否需要用格式符包裹页码     

    /**
     * private
     *
     */
    private $pagebarnum = 10;   // 控制记录条的个数
    private $totalpage = 0;    // 总页数
    private $totalnum = 0;    // 总记录数
    private $ajax_action_name = ''; // AJAX动作名
    private $nowindex = 1;  // 当前页
    private $url = '';          // url地址头
    private $offset = 0;
    private $perpage = 0;
    private $rep = false;

    /**
     * constructor构造函数
     *
     * @param array $array['total'], $array['perpage'], $array['nowindex'], $array['url'], $array['ajax']
     */
    function __construct($array) {
        if (is_array($array)) {
            if (!array_key_exists('total', $array))
                $this->error(__FUNCTION__, 'need a param of total');
            $total = intval($array['total']);
            $perpage = (array_key_exists('perpage', $array)) ? intval($array['perpage']) : 10;
            $nowindex = (array_key_exists('nowindex', $array)) ? intval($array['nowindex']) : '';
            $url = (array_key_exists('url', $array)) ? $array['url'] : '';
        } else {
            $total = $array;
            $perpage = 10;
            $nowindex = '';
            $url = '';
        }

        if ((!is_int($total)) || ($total < 0))
            $this->error(__FUNCTION__, $total . ' is not a positive integer!');
        if ((!is_int($perpage)) || ($perpage <= 0))
            $this->error(__FUNCTION__, $perpage . ' is not a positive integer!');

        if (!empty($array['page_name']))
            $this->set('page_name', $array['page_name']);   // 设置pagename

        $this->_set_nowindex($nowindex);    // 设置当前页
        $this->_set_url($url);              // 设置链接地址
        $this->totalpage = ceil($total / $perpage);
        $this->offset = ($this->nowindex - 1) * $this->perpage;
        $this->totalnum = $total;

        if (!empty($array['ajax']))
            $this->open_ajax($array['ajax']);   // 打开AJAX模式
    }

    /**
     * 设定类中指定变量名的值，如果改变量不属于这个类，将throw一个exception
     *
     * @param string $var
     * @param string $value
     */
    function set($var, $value) {
        if (in_array($var, get_object_vars($this))) {
            $this->$var = $value;
        } else {
            $this->error(__FUNCTION__, $var . ' does not belong to ' . __CLASS__);
        }
    }

    /**
     * 打开倒AJAX模式
     *
     * @param string $action 默认ajax触发的动作。
     */
    function open_ajax($action) {
        $this->is_ajax = true;
        $this->ajax_action_name = $action;
    }

    /**
     * 获取显示"下一页"的代码
     * 
     * @param string $style
     * @return string
     */
    function next_page($style = '') {
        if ($this->nowindex < $this->totalpage) {
            return '<' . $this->tag_html . ' class="' . $style . '">' . $this->_get_text($this->_get_link($this->_get_url($this->nowindex + 1), $this->next_page, $style)) . '</' . $this->tag_html . '>';
        }
        return '<' . $this->tag_html . ' class="' . $style . '">' . $this->_get_text($this->next_page) . '</' . $this->tag_html . '>';
    }

    /**
     * 获取显示“上一页”的代码
     *
     * @param string $style
     * @return string
     */
    function pre_page($style = '') {
        if ($this->nowindex > 1) {
            return '<' . $this->tag_html . ' class="' . $style . '">' . $this->_get_text($this->_get_link($this->_get_url($this->nowindex - 1), $this->pre_page, $style)) . '</' . $this->tag_html . '>';
        }
        return '<' . $this->tag_html . ' class="' . $style . '">' . $this->_get_text($this->pre_page) . '</' . $this->tag_html . '>';
    }

    /**
     * 获取显示“首页”的代码
     *
     * @return string
     */
    function first_page($style = '') {
        if ($this->nowindex == 1) {
            return '<' . $this->tag_html . ' class="' . $style . '">' . $this->_get_text($this->first_page) . '</' . $this->tag_html . '>';
        }
        return '<' . $this->tag_html . ' class="' . $style . '">' . $this->_get_text($this->_get_link($this->_get_url(1), $this->first_page, $style)) . '</' . $this->tag_html . '>';
    }

    /**
     * 获取显示“尾页”的代码
     *
     * @return string
     */
    function last_page($style = '') {
        if ($this->nowindex == $this->totalpage) {
            return '<' . $this->tag_html . ' class="' . $style . '">' . $this->_get_text($this->last_page) . '</' . $this->tag_html . '>';
        }
        return '<' . $this->tag_html . ' class="' . $style . '">' . $this->_get_text($this->_get_link($this->_get_url($this->totalpage), $this->last_page, $style)) . '</' . $this->tag_html . '>';
    }

    /**
     * 获取数字条代码
     *
     * @return string
     */
    function nowbar($style = '', $nowindex_style = '') {
        $plus = ceil($this->pagebarnum / 2);
        if ($this->pagebarnum - $plus + $this->nowindex > $this->totalpage)
            $plus = ($this->pagebarnum - $this->totalpage + $this->nowindex);

        $begin = $this->nowindex - $plus + 1;
        $begin = ($begin >= 1) ? $begin : 1;
        $return = '';
        for ($i = $begin; $i < $begin + $this->pagebarnum; $i++) {
            if ($i <= $this->totalpage) {
                if ($i != $this->nowindex)
                    $return .= '<' . $this->tag_html . '>' . ($this->_get_text($this->_get_link($this->_get_url($i), $i, $style))) . '</' . $this->tag_html . '>';
                else
                    $return .= '<' . $this->tag_html . ' class="' . $nowindex_style . '">' . ($this->_get_text($i)) . '</' . $this->tag_html . '>';
            } else {
                break;
            }
            $return .= PHP_EOL;
        }
        unset($begin);
        return $return;
    }

    /**
     * 获取显示跳转按钮的代码
     *
     * @return string
     */
    function select() {
        $return = '<select name="PB_Page_Select" onchange="javascript:window.location.href=\'' . strtr($this->url, array('{n}'=>'\'+(this.options[this.selectedIndex].value>1?this.options[this.selectedIndex].value:\'\')+\'')) . '\'+this.options[this.selectedIndex].value">';
        for ($i = 1; $i <= $this->totalpage; $i++) {            
            if ($i == $this->nowindex) {
                $return .= '<option value="' . $i . '" selected>' . $i . '</option>';
            } else {
                $return .= '<option value="' . $i . '">' . $i . '</option>';
            }
        }
        unset($i);
        $return .= '</select>';
        return $return;
    }

    /**
     * 获取mysql 语句中limit需要的值
     *
     * @return string
     */
    function offset() {
        return $this->offset;
    }

    /**
     * 控制分页显示风格（可以增加相应的风格）
     *
     * @param int $mode
     * @param boolean $
     * @return string
     */
    function show($mode = 1) {
        switch ($mode) {
            case '1':
                $this->next_page = '下一页';
                $this->pre_page = '上一页';
                return $this->pre_page() . $this->nowbar() . $this->next_page() . '&nbsp;当前第' . $this->select() . '/' . $this->totalpage . '页&nbsp;&nbsp;共' . $this->totalnum . '条记录';
                break;
            case '2':
                $this->next_page = '下一页';
                $this->pre_page = '上一页';
                $this->first_page = '首页';
                $this->last_page = '尾页';
                return $this->first_page() . $this->pre_page() . '[第' . $this->nowindex . '页]' . $this->next_page() . $this->last_page() . '第' . $this->select() . '页';
                break;
            case '3':
                $this->next_page = '下一页';
                $this->pre_page = '上一页';
                $this->first_page = '首页';
                $this->last_page = '尾页';
                return $this->first_page() . $this->pre_page() . $this->next_page() . $this->last_page();
                break;
            case '4':
                $this->next_page = '下一页';
                $this->pre_page = '上一页';
                return $this->pre_page() . $this->nowbar() . $this->next_page();
                break;
            case '5':
                return $this->pre_bar() . $this->pre_page() . $this->nowbar() . $this->next_page() . $this->next_bar();
                break;
        }
    }

    /**
     * 设置url头地址
     * @param: String $url
     * @return boolean
     */
    private function _set_url($url = '') {
        if (!empty($url)) {
            // 手动设置
            $this->url = $url . ((stristr($url, '?')) ? '&' : '?') . $this->page_name . '=';
        } else {
            // 自动获取
            if (empty($_SERVER['QUERY_STRING'])) {
                // 不存在QUERY_STRING时
                $this->url = $_SERVER['REQUEST_URI'] . "?" . $this->page_name . '=';
            } else {
                //
                if (stristr($_SERVER['QUERY_STRING'], $this->page_name . '=')) {
                    // 地址存在页面参数
                    $this->url = str_replace($this->page_name . '=' . $this->nowindex, '', $_SERVER['REQUEST_URI']);
                    $last = $this->url[strlen($this->url) - 1];

                    if ($last == '?' || $last == '&') {
                        $this->url .= $this->page_name . "=";
                    } else {
                        $this->url .= '&' . $this->page_name . "=";
                    }
                } else {
                    $this->url = $_SERVER['REQUEST_URI'] . '&' . $this->page_name . '=';
                }
            }
        }
    }

    /**
     * 设置当前页面
     *
     */
    private function _set_nowindex($nowindex) {
        // 手动设置
        $this->nowindex = intval($nowindex);

        if (empty($nowindex) && isset($_GET[$this->page_name]))
            $this->nowindex = intval($_GET[$this->page_name]);
    }

    /**
     * 为指定的页面返回地址值
     *
     * @param int $pageno
     * @return string $url
     */
    private function _get_url($pageno = 1) {
        return empty($this->rep) ? $this->url . $pageno : strtr($this->url, array('{n}'=>$pageno > 1 ? $pageno : '')) . $pageno;
    }

    /**
     * 获取分页显示文字，比如说默认情况下_get_text('<a href="">1</a>')将返回[<a href="">1</a>]
     *
     * @param String $str
     * @return string $url
     */
    private function _get_text($str) {
        return ($this->is_format === true ? $this->format_left : '') . $str . ($this->is_format === true ? $this->format_right : '');
    }

    /**
     * 获取链接地址
     */
    private function _get_link($url, $text, $style = '') {
        $style = (empty($style)) ? '' : 'class="' . $style . '"';
        if ($this->is_ajax) {
            // 如果是使用AJAX模式
            return '<a ' . $style . ' href="javascript:' . $this->ajax_action_name . '(\'' . $url . '\')">' . $text . '</a>';
        } else {
            return '<a ' . $style . ' href="' . $url . '">' . $text . '</a>';
        }
    }

    /**
     * 出错处理方式
     */
    function error($function, $errormsg) {
        exit('Error in file <b>' . __FILE__ . '</b> ,Function <b>' . $function . '()</b> :' . $errormsg);
    }

}
?> 
