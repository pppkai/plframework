<?php

/**
 +------------------------------------------------------------------------------
 * 列表 表单生成类
 +------------------------------------------------------------------------------
 * @category PLFrame
 * @package  Include
 * @author   pengzl <pengzl_gz@163.com>
 * @version  $Id: makeTable.class.php 2 2015-11-02 02:28:48Z pengzl $
 +------------------------------------------------------------------------------
 */
class makeTable {

    /**
     * @var data stored here
     */
    private static $_PREFIX = 'm_'; // 控件名前缀
    private static $m_symbol = ','; // 指定值分隔符
    private static $m_name = null; // 控件名
    private static $m_title = null; // 表头项
    private static $m_PK = 'id'; // 主键
    private static $m_data = null; // 列表数据源
    private static $m_control = null; // 修改/删除等控制参数
    private static $m_escape = null; // 字段值转义控制
    private static $m_instance = null; // 单例对象
    private static $m_valid_act = array('onclick', 'onmouseover', 'onmouseout', 'onmousemove', 'onchange', 'onfocus', 'onblur'); // 有效的JS事件名称
    // 表标记参数 
    private static $m_tags = array('tags' => array('name' => 'table'), 'tagsrow' => array('name' => 'tr'), 'tagscol' => array('name' => 'td'));

    private function __construct($_tableName = 'default') {
        self::$m_name = $_tableName;
    }

    /**
     * @desc 创建单例对象
     */
    static function getInstance() {
        if (!is_object(self::$m_instance)) {
            self::$m_instance = new self();
        }
        return self::$m_instance;
    }

    /**
     * @desc 生成显示列表方法
     */
    function showTableList($_data = null) { //列表
        $_rtn = '';
        if ($_data && is_array($_data)) {
            self::$m_data = $_data;
        }

        // 转义数据源中某些字段 PK字段禁止转义
        self::doEscape();
        
        // 取表头部分
        $_rtn .= self::tableCaput();

        $_titlecss = is_array(self::$m_tags['tagsrow']['rules']['class']) ? self::$m_tags['tagsrow']['rules']['class'][0] : (isset(self::$m_tags['tagsrow']['rules']['class']) ? self::$m_tags['tagsrow']['rules']['class'] : '');

        $_rtn .= '<' . self::$m_tags['tagsrow']['name'];
        $_rtn .= $_titlecss ? " class=\"{$_titlecss}\">" : '>';

        $_values = array_values(self::$m_title);
        $_keys = array_keys(self::$m_title);        
        foreach ($_values as $k=>$value) {
            // 响应排序参数
            if (is_array($value)) {                
                if (isset($value[1]) && in_array(strtolower($value[1]), array('asc', 'desc'))) {
                    // 生成用于排序的链接
                    if (isset(self::$m_control['control']['con_parse']) && isset(self::$m_control['control']['con_parse'][0])) {
                        $value[0] = '<div class="hover_a"><a href="'.(self::$m_control['control']['con_parse'][0] . '&order=' . $_keys[$k] . '-' . strtolower($value[1])).'">'.$value[0].'</a></div>';
                    }
                } 
                $value = $value[0];
            }
            $_rtn .= '<' . self::$m_tags['tagscol']['name'] . ">{$value}</" . self::$m_tags['tagscol']['name'] . '>';
        }

        if (isset(self::$m_control['control'])) {
            $_rtn .= '<' . self::$m_tags['tagscol']['name'] . ">操作</" . self::$m_tags['tagscol']['name'] . '>';
        }

        $_rtn .= '</' . self::$m_tags['tagsrow']['name'] . '>';

        // 输出数据列表
        $fileds = array_keys(self::$m_title);
        foreach (self::$m_data as $key => $value) {
            $_rtn .= '<' . self::$m_tags['tagsrow']['name'] . '';

            $_rtn .= (isset(self::$m_tags['tagsrow']['rules']['rul']) ? self::classRules($key + 1, self::$m_tags['tagsrow']['rules']['rul'], self::$m_tags['tagsrow']['rules']['class']) : (isset(self::$m_tags['tagsrow']['rules']['class'][1]) ? ' class="' . self::$m_tags['tagsrow']['rules']['class'][1] . '"' : '')) . '>';

            foreach ($fileds as $k => $v) {
                $_cbox = '';
                if (is_array(self::$m_title[$v]) && self::$m_title[$v][1] == 'checkbox') {
                    $_cbox = self::doInput('chck', 'checkbox', $value[self::$m_PK]);
                } else if (is_array(self::$m_title[$v]) && self::$m_title[$v][1] == 'img') {                    
                    $_cbox = self::doImg('preimg', self::$m_title[$v][2] . $value[$v]);
                    
                    // 不打印出文件名
                    $value[$v] = '';
                }

                $_rtn .= '<' . self::$m_tags['tagscol']['name'] . (isset(self::$m_tags['tagscol']['rules']) ? self::classRules($k + 1, self::$m_tags['tagscol']['rules']['rul'], self::$m_tags['tagscol']['rules']['class']) : '') . '>';
                $_rtn .= "{$_cbox} " . (isset($value[$v]) ? (empty($value[$v]) && !is_numeric($value[$v]) ? '&nbsp;' : $value[$v]) : '&nbsp;') . '</' . self::$m_tags['tagscol']['name'] . '>';
            }

            if (isset(self::$m_control['control'])) {
                if (isset(self::$m_control['control']['parse_mode']) && self::$m_control['control']['parse_mode'] == 'rewrite') {
                    $_con = self::$m_control['control']['con_parse'][0] . '/' . self::$m_control['control']['con_parse'][1] . '/';
                } else if (stripos(self::$m_control['control']['con_parse'][0], '?') === false) {
                    $_con = self::$m_control['control']['con_parse'][0] . "?" . self::$m_control['control']['con_parse'][1] . "=";
                } else {
                    $_con = self::$m_control['control']['con_parse'][0] . "&" . self::$m_control['control']['con_parse'][1] . "=";
                }

                // 输出控制选项               
                $_rtnc = '';
                if (isset(self::$m_control['control']['class'])) {
                    $_rtn .= '<' . self::$m_tags['tagscol']['name'] . self::$m_control['control']['class'] . '>';
                } else {
                    $_rtn .= '<' . self::$m_tags['tagscol']['name'] . '>';
                }

                foreach (self::$m_control['control']['con_list'] as $k => $v) {
                    if (is_array($v)) {
                        $_rtnc .= self::doParseByFunc($v, $value);
                    } else {
                        if (isset(self::$m_control['control']['parse_mode']) && self::$m_control['control']['parse_mode'] == 'rewrite') {
                            $_rtnc .= "|&nbsp;<a href=\"{$_con}{$k}/id/" . $value[self::$m_PK] . "\">{$v}</a>";
                        } else {
                            $_rtnc .= "|&nbsp;<a href=\"{$_con}{$k}&id=" . $value[self::$m_PK] . "\"".(isset(self::$m_control['control']['target'][$k])?' target="'.self::$m_control['control']['target'][$k].'"':'').">{$v}</a>";
                        }
                    }
                }

                $_rtn .= substr($_rtnc, 1) . '</' . self::$m_tags['tagscol']['name'] . '>';
            }
            $_rtn .= '</' . self::$m_tags['tagsrow']['name'] . '>';
        }

        // 取表尾
        $_rtn .= self::tableTail();

        // 按钮
        $_rtn .= self::submitButton(false);
        
        return $_rtn;
    }

    /**
     * @desc 生成显示列表扩展方法
     */
    function showTableListEx($_dStr = null) {
        $_rtn = '';

        // 转义数据源中某些字段 PK字段禁止转义
        self::doEscape();

        // 取表头部分
        $_rtn .= self::tableCaput();
        $_titlecss = '';
        if (isset(self::$m_tags['tagsrow']['rules'])) {
            $_titlecss = is_array(self::$m_tags['tagsrow']['rules']['class']) ? self::$m_tags['tagsrow']['rules']['class'][0] : (isset(self::$m_tags['tagsrow']['rules']['class']) ? self::$m_tags['tagsrow']['rules']['class'] : '');
        }

        $_rtn .= '<' . self::$m_tags['tagsrow']['name'];
        $_rtn .= $_titlecss ? " class=\"{$_titlecss}\">" : '>';

        $_values = array_values(self::$m_title);
        $_keys = array_keys(self::$m_title);      
        foreach ($_values as $k=>$value) {
            // 响应排序参数
            if (is_array($value)) {                
                if (isset($value[1]) && in_array(strtolower($value[1]), array('asc', 'desc'))) {
                    // 生成用于排序的链接
                    if (isset(self::$m_control['control']['con_parse']) && isset(self::$m_control['control']['con_parse'][0])) {
                        $value[0] = '<div class="hover_a"><a href="'.(self::$m_control['control']['con_parse'][0] . '&order=' . $_keys[$k] . '-' . strtolower($value[1])).'">'.$value[0].'</a></div>';
                    }
                } 
                $value = $value[0];
            }
            $_rtn .= '<' . self::$m_tags['tagscol']['name'] . ">{$value}</" . self::$m_tags['tagscol']['name'] . '>';
        }

        if (isset(self::$m_control['control'])) {
            $_rtn .= '<' . self::$m_tags['tagscol']['name'] . ">操作</" . self::$m_tags['tagscol']['name'] . '>';
        }

        $_rtn .= '</' . self::$m_tags['tagsrow']['name'] . '>';

        // 取表尾
        $_rtn .= self::tableTail();

        // 取表头部分
        $_rtn .= self::tableCaput($_dStr);

        // 输出数据列表
        $fileds = array_keys(self::$m_title);
        foreach (self::$m_data as $key => $value) {
            $_rtn .= '<' . self::$m_tags['tagsrow']['name'] . '';

            // 取行绑定事件 array('act'=>'onclick', 'func'=>'alert()', 'parse'=>array(), 'rules'=>array('行数1', '行数2')||'行数1,行数2')  规则为空则表示全部
            if (isset(self::$m_tags['tagsrow']['function']) && is_array(self::$m_tags['tagsrow']['function'])) {
                if (isset(self::$m_tags['tagsrow']['function'][0]['act'])) {
                    foreach (self::$m_tags['tagsrow']['function'] as $val_arr) {
                        $_rtn .= self::getFuncByList($val_arr, $value, ($key + 1));
                    }
                } else {
                    $_rtn .= self::getFuncByList(self::$m_tags['tagsrow']['function'], $value, ($key + 1));
                }
            }

            $_rtn .= (isset(self::$m_tags['tagsrow']['rules']['rul']) ? self::classRules($key + 1, self::$m_tags['tagsrow']['rules']['rul'], self::$m_tags['tagsrow']['rules']['class']) : (isset(self::$m_tags['tagsrow']['rules']['class'][1]) ? ' class="' . self::$m_tags['tagsrow']['rules']['class'][1] . '"' : '')) . '>';

            foreach ($fileds as $k => $v) {
                $_cbox = '';
                if (is_array(self::$m_title[$v]) && self::$m_title[$v][1] == 'checkbox') {
                    $_cbox = self::doInput('chck', 'checkbox', $value[self::$m_PK]);
                } else if (is_array(self::$m_title[$v]) && self::$m_title[$v][1] == 'img') {                    
                    $_cbox = self::doImg('preimg', self::$m_title[$v][2] . '/' . $value[$v]);
                }

                // 取列绑定事件 array('act'=>'onclick', 'func'=>'alert()', 'parse'=>array(), 'rules'=>array('行数1|列数1', '行数2|列数2')||'行数1|列数1,行数2|列数2') 此处行数可用*表示所有行 规则为空则表示全部
                $_col_func = '';
                if (isset(self::$m_tags['tagscol']['function']) && is_array(self::$m_tags['tagscol']['function'])) {
                    if (isset(self::$m_tags['tagscol']['function'][0]['act'])) {
                        foreach (self::$m_tags['tagscol']['function'] as $val_arr) {
                            $_col_func .= self::getFuncByList($val_arr, $value, ($key + 1), ($k + 1));
                        }
                    } else {
                        $_col_func = self::getFuncByList(self::$m_tags['tagscol']['function'], $value, ($key + 1), ($k + 1));
                    }
                }

                $_rtn .= '<' . self::$m_tags['tagscol']['name'] . (isset(self::$m_tags['tagscol']['rules']) ? self::classRules($k + 1, self::$m_tags['tagscol']['rules']['rul'], self::$m_tags['tagscol']['rules']['class']) : '') . ' ' . $_col_func . '>';
                $_rtn .= "{$_cbox} " . (isset($value[$v]) ? (empty($value[$v]) && !is_numeric($value[$v]) ? '&nbsp;' : $value[$v]) : '&nbsp;') . '</' . self::$m_tags['tagscol']['name'] . '>';
            }

            if (isset(self::$m_control['control'])) {
                if (isset(self::$m_control['control']['parse_mode']) && self::$m_control['control']['parse_mode'] == 'rewrite') {
                    $_con = self::$m_control['control']['con_parse'][0] . '/' . self::$m_control['control']['con_parse'][1] . '/';
                } else if (stripos(self::$m_control['control']['con_parse'][0], '?') === false) {
                    $_con = self::$m_control['control']['con_parse'][0] . '?' . self::$m_control['control']['con_parse'][1] . '=';
                } else {
                    $_con = self::$m_control['control']['con_parse'][0] . '&' . self::$m_control['control']['con_parse'][1] . '=';
                }

                // 输出控制选项               
                $_rtnc = '';
                if (isset(self::$m_control['control']['class'])) {
                    $_rtn .= '<' . self::$m_tags['tagscol']['name'] . self::$m_control['control']['class'] . '>';
                } else {
                    $_rtn .= '<' . self::$m_tags['tagscol']['name'] . '>';
                }

                foreach (self::$m_control['control']['con_list'] as $k => $v) {
                    if (is_array($v)) {
                        $_rtnc .= self::doParseByFunc($v, $value);
                    } else {
                        if (isset(self::$m_control['control']['parse_mode']) && self::$m_control['control']['parse_mode'] == 'rewrite') {
                            $_rtnc .= "|&nbsp;<a href=\"{$_con}{$k}/id/" . $value[self::$m_PK] . "\">{$v}</a>";
                        } else {
                            $_rtnc .= "|&nbsp;<a href=\"{$_con}{$k}&id=" . $value[self::$m_PK] . "\">{$v}</a>";
                        }
                    }
                }
                $_rtn .= substr($_rtnc, 1) . '</' . self::$m_tags['tagscol']['name'] . '>';
            }
            $_rtn .= '</' . self::$m_tags['tagsrow']['name'] . '>';
        }

        // 取表尾
        $_rtn .= self::tableTail();

        // 按钮
        $_rtn .= self::submitButton(false);

        return $_rtn;
    }
    
    /**
     * @desc 生成纵向显示列表
     */
    function colspanTableList($_data = null) {
        $_rtn = '';
        if ($_data && is_array($_data)) {
            //$tmp_data = array_slice($_data, 0, 1);
            $tmp_data = current($_data);
            self::$m_data = is_array($tmp_data) ? $_data : array($_data);
        }        

        // 转义数据源中某些字段 PK字段禁止转义
        self::doEscape();

        // 取表头部分
        $_rtn .= self::tableCaput();
        
        // 添加表单标题
        $_rtn .= self::submitTitle();        
        
        // 加row class
        $_row_head = !empty(self::$m_tags['tagsrow']['name']) ? '<' . self::$m_tags['tagsrow']['name'] : '';
        if (!empty(self::$m_tags['tagsrow']['name']) && !empty(self::$m_tags['tagsrow']['rules']['class'])) {
            $class_name = self::$m_tags['tagsrow']['rules']['class'];
            if (is_array($class_name)) $class_name = $class_name[0];
            $_row_head .= ' class="' . $class_name . '">';                
        } else if (!empty(self::$m_tags['tagsrow']['name'])) {
            $_row_head .= '>';
        }
        $_row_foot = !empty(self::$m_tags['tagsrow']['name']) ? '</' . self::$m_tags['tagsrow']['name'] . '>' : '';

        // 加col class
        $_col_head = !empty(self::$m_tags['tagscol']['name']) ? '<' . self::$m_tags['tagscol']['name'] : '';
        if (!empty(self::$m_tags['tagscol']['name']) && !empty(self::$m_tags['tagscol']['rules']['class'])) {
            $class_name = self::$m_tags['tagscol']['rules']['class'];
            if (is_array($class_name)) $class_name = $class_name[0];
            $_col_head .= ' class="' . $class_name . '">';                
        } else if (!empty(self::$m_tags['tagscol']['name'])) {
            $_col_head .= '>';
        }
        $_col_foot = !empty(self::$m_tags['tagscol']['name']) ? '</' . self::$m_tags['tagscol']['name'] . '>' : '';        
            
        $_fileds = array_keys(self::$m_title);     
        ////dump(self::$m_data);
        
        foreach (self::$m_data as $k => $val) {
            $_rtn .= '<div class="dtable">';
            foreach ($_fileds as $value) {  
                $_rtn .= $_row_head;
                // 取数据 
                if (isset(self::$m_title[$value][2]) && is_array(self::$m_title[$value][2])) {
                    $_rtn .= $_col_head . self::$m_title[$value][2]['name'] . $_col_foot;               
                } else if (is_array(self::$m_title[$value]) && isset(self::$m_title[$value][2])) {
                    $_rtn .= $_col_head . self::$m_title[$value][2] . $_col_foot;
                } else if (is_array(self::$m_title[$value]) && isset(self::$m_title[$value][1])) {
                    $_rtn .= $_col_head . (self::$m_title[$value][1] == 'checkbox' ? self::$m_title[$value][0] : self::$m_title[$value][1]) . $_col_foot;
                } else if (is_array(self::$m_title[$value]) && isset(self::$m_title[$value][0])) {
                    $_rtn .= $_col_head . self::$m_title[$value][0] . $_col_foot;
                } else {
                    $_rtn .= $_col_head . self::$m_title[$value] . $_col_foot;
                }
                
                ////dump($val, '$val:');
                
                if (self::$m_title[$value][0] == 'img') {                    
                    $_rtn .= $_col_head . self::doImg('pic_src', self::$m_title[$value][1], self::$m_title[$value][2]) . $_col_foot;
                } else {
                    $_rtn .= $_col_head . (isset($val[$value]) ? $val[$value] : '') . $_col_foot;
                }
                $_rtn .= $_row_foot;
            }            
            $_rtn .= '</div>';
        }

        // 取表尾
        $_rtn .= self::tableTail();
        
        return $_rtn;        
    }    
    

    /**
     * @desc 生成提交表单方法
     */
    function showTableSubmit($_data = null, $_notcaput = false) {
        $_rtn = '';
        if ($_data && is_array($_data)) {
            self::$m_data = $_data;
        }

        // 取表单头部
        $_rtn .= self::submitCaput();

        // 取表头部分
        $_rtn .= self::tableCaput();

        // 添加表单标题
        $_rtn .= self::submitTitle();

        $_fileds = array_keys(self::$m_title);
        foreach ($_fileds as $value) {
            $_parse = self::doParse($value, self::$m_title[$value][0], self::$m_title[$value][1]);
            $_method = 'do' . ucfirst(self::$m_title[$value][0]);            
            
            // 加row class
            $_rtn .= !empty(self::$m_tags['tagsrow']['name']) ? '<' . self::$m_tags['tagsrow']['name'] : '';
            if (!empty(self::$m_tags['tagsrow']['name']) && !empty(self::$m_tags['tagsrow']['rules']['class'])) {
                $class_name = self::$m_tags['tagsrow']['rules']['class'];
                if (is_array($class_name)) $class_name = $class_name[0];
                $_rtn .= ' class="' . $class_name . '">';                
            } else if (!empty(self::$m_tags['tagsrow']['name'])) {
                $_rtn .= '>';
            }
            
            // 加col class
            $_rtn .= !empty(self::$m_tags['tagscol']['name']) ? '<' . self::$m_tags['tagscol']['name'] : '';
            if (!empty(self::$m_tags['tagscol']['name']) && !empty(self::$m_tags['tagscol']['rules']['class'])) {
                $class_name = self::$m_tags['tagscol']['rules']['class'];
                if (is_array($class_name)) $class_name = $class_name[0];
                $_rtn .= ' class="' . $class_name . '">';                
            } else if (!empty(self::$m_tags['tagscol']['name'])) {
                $_rtn .= '>';
            }            

            if (isset(self::$m_title[$value][2]) && is_array(self::$m_title[$value][2])) {
                !isset(self::$m_title[$value][3]) ? self::$m_title[$value][3] = '' : '';
                (self::$m_title[$value][0] == 'select') ? $_rtn .= '<span class="field">' . self::$m_title[$value][2]['name'] . ":</span>" . self::doSelectCaput(self::$_PREFIX . $value, self::$m_title[$value][3]) : $_rtn .= '<span class="field">' . self::$m_title[$value][2]['name'] . ":</span>";

                foreach (self::$m_title[$value][2]['vlist'] as $k => $v) {
                    $_parse = self::doParse($value, self::$m_title[$value][0], self::$m_title[$value][1], $k);

                    $_rtn .= (self::$m_title[$value][0] == 'select') ? call_user_func_array(array(&$this, $_method), $_parse) : call_user_func_array(array(&$this, $_method), $_parse) . "{$v} ";
                }

                (self::$m_title[$value][0] == 'select') ? $_rtn .= self::doSelectTail() : '';
            } else if (!isset(self::$m_title[$value][2])) {
                $_tmp = self::$m_tags['tagsrow']['name'] ? '<' . self::$m_tags['tagsrow']['name'] . '>' : '';
                $_tmp .= self::$m_tags['tagscol']['name'] ? '<' . self::$m_tags['tagscol']['name'] . '>' : '';
                $_rtn = substr($_rtn, 0, mb_strlen($_rtn) - mb_strlen($_tmp)) . call_user_func_array(array(&$this, $_method), $_parse);
                continue;
            } else {
                $_rtn .= '<span class="field">' . self::$m_title[$value][2] . ":</span><span class='values'>" . call_user_func_array(array(&$this, $_method), $_parse) . '</span>';
            }
            $_rtn .= !empty(self::$m_tags['tagscol']['name']) ? '</' . self::$m_tags['tagscol']['name'] . '>' : '';
            $_rtn .= !empty(self::$m_tags['tagsrow']['name']) ? '</' . self::$m_tags['tagsrow']['name'] . '>' : '';
        }

        // 按钮
        $_rtn .= self::submitButton();

        // 取表尾
        $_rtn .= self::tableTail();

        // 取表单尾
        $_rtn .= self::submitTail();

        return $_rtn;
    }

    /**
     * @desc 生成提交表单头部方法
     * self::$m_tags['tagsform']['attr'] = array() 表单属性
     * self::$m_tags['tagsform']['custom'] = '' 自定义项 exp: <input name='onclick' type='hidden' value='123' /> 
     * self::$m_tags['tagsform']['button'] = array('input','text','名称') 表单按钮 
     * 可以多个 exp: array(array('submit','submit','提交'), array('reset','reset','重置')) 
     */
    private static function submitCaput() {
        $_rtn_cap = "<form";
        if (isset(self::$m_tags['tagsform']['attr'])) {
            foreach (self::$m_tags['tagsform']['attr'] as $key => $value) {
                $_rtn_cap .= " {$key}=\"{$value}\"";
            }
        }
        $_rtn_cap .= '>';

        if (isset(self::$m_tags['tagsform']['custom'])) {
            $_rtn_cap .= self::$m_tags['tagsform']['custom'];
        }

        return $_rtn_cap;
    }

    /**
     * @desc 生成提交表单按扭方法
     * $_isSide 在表框框里面还是外面
     */
    private function submitButton($_isSide = true) {
        $_rtn_tail = '';
        $_parse = array();

        if (isset(self::$m_tags['tagsform']['button']) && is_array(self::$m_tags['tagsform']['button'])) {
            $_rtn_tail .= $_isSide ? ( !empty(self::$m_tags['tagsrow']['name']) ? '<' . self::$m_tags['tagsrow']['name'] . '>' : '' ) : '';

            if (is_array(self::$m_tags['tagsform']['button'][0])) {
                foreach (self::$m_tags['tagsform']['button'] as $key => $value) {
                    $_parse = array();
                    foreach ($value as $k => $v) {
                        $v ? array_push($_parse, $v) : array_push($_parse, '');
                    }

                    $_rtn_tail .= $_isSide ? ( !empty(self::$m_tags['tagscol']['name']) ? '<' . self::$m_tags['tagscol']['name'] . '>' : '' ) : '';
                    $_rtn_tail .= call_user_func_array(array(&$this, 'doInput'), $_parse);
                    $_rtn_tail .= $_isSide ? ( !empty(self::$m_tags['tagscol']['name']) ? '</' . self::$m_tags['tagscol']['name'] . '>' : '' ) : '';
                }
            } else {
                $_rtn_tail .= $_isSide ? ( !empty(self::$m_tags['tagscol']['name']) ? '<' . self::$m_tags['tagscol']['name'] . '>' : '' ) : '';
                foreach (self::$m_tags['tagsform']['button'] as $k => $v) {
                    $v ? array_push($_parse, $v) : array_push($_parse, '');
                }

                $_rtn_tail .= call_user_func_array(array(&$this, 'doInput'), $_parse);
                $_rtn_tail .= $_isSide ? ( !empty(self::$m_tags['tagscol']['name']) ? '</' . self::$m_tags['tagscol']['name'] . '>' : '' ) : '';
            }
            $_rtn_tail .= $_isSide ? ( !empty(self::$m_tags['tagsrow']['name']) ? '</' . self::$m_tags['tagsrow']['name'] . '>' : '' ) : '';
        }
        return $_rtn_tail;
    }
    
    /**
     * @desc 获取表单按钮单项控件HTML
     */
    function getFormHtml($_data = null, $_func = null) {
        $_rtn = array();
        if (empty($_data)) return $_rtn;
        foreach ($_data as $name => $val) {
            $_str = null;
            $_parse = self::doParse($name, $val[0], $val[1]);
            $_method = 'do' . ucfirst($val[0]);
            if (isset($val[2]) && is_array($val[2])) {
                !isset($val[3]) ? $val[3] = '' : '';
                $_str = ($val[0] == 'select') ? $val[2]['name'] . ':' . self::doSelectCaput(self::$_PREFIX . $name, $val[3]) : $val[2]['name'] . ':';
                foreach ($val[2]['vlist'] as $k => $v) {
                    $_parse = self::doParse($name, $val[0], $val[1], $k);
                    $_str .= ($val[0] == 'select') ? call_user_func_array(array(&$this, $_method), $_parse) : call_user_func_array(array(&$this, $_method), $_parse) . "{$v} ";
                }
                $_str .= ($val[0] == 'select') ? self::doSelectTail() : '';
            } else {
                isset($val[2]) && isset($val[3]) && isset($val[4]) && $_parse = self::doParse($name, $val[0], $val[1], $val[2], $val[3], $val[4]);
                isset($val[2]) && isset($val[3]) && !isset($val[4]) && $_parse = self::doParse($name, $val[0], $val[1], $val[2], $val[3]);
                isset($val[2]) && !isset($val[3]) && $_parse = self::doParse($name, $val[0], $val[1], $val[2]);
                $_str .= call_user_func_array(array(&$this, $_method), $_parse);
            }
            
            // build event eg:array('name' => array('method' => 'onclick', 'func' => '$(this).mlhidden', 'p' => '参数串'))
            if ($_str && $_func && !empty($_func[$name])) {                
                $_str_func = " {$_func[$name]['method']}=\"javascript:{$_func[$name]['func']}('" . (isset($_func[$name]['p']) ? $_func[$name]['p'] : '') . "');\"";
                $_str = str_replace('<' . $val[0], '<' . $val[0] . $_str_func, $_str);
            }
            
            $_rtn[] = $_str;
        }
        return $_rtn;
    }    

    /**
     * @desc 生成提交表单尾部方法
     */
    private static function submitTail() {
        return "</form>";
    }

    /**
     * @desc 生成表头方法
     */
    private static function tableCaput($_data = null) {
        if ($_data) {
            $_data[0] ? self::$m_name = $_data[0] : '';
            $_data[1] ? self::$m_tags['tags']['attr'] = $_data[1] : '';
        }

        $_rtn_cap = '<' . self::$m_tags['tags']['name'] . ' id="' . self::$m_name . '"';
        if (isset(self::$m_tags['tags']['attr'])) {
            foreach (self::$m_tags['tags']['attr'] as $key => $value) {
                $_rtn_cap .= " {$key}=\"{$value}\"";
            }
        }
        $_rtn_cap .= '>';
        return $_rtn_cap;
    }

    /**
     * @desc 生成表尾部方法
     */
    private static function tableTail() {
        return self::$m_tags['tags']['name'] ? '</' . self::$m_tags['tags']['name'] . '>' : '';
    }

    /**
     * @desc 生成提交表单标题方法
     */
    private static function submitTitle() {
        return isset(self::$m_tags['tagstitle']['name']) ? '<' . self::$m_tags['tagstitle']['name'] . '>' . self::$m_tags['tagstitle']['title'] . '</' . self::$m_tags['tagstitle']['name'] . '>' : '';
    }

    /**
     * @desc 分析加CLASS规则方法
     */
    private static function classRules($num, $_rules = null, $_rulstr) {
        $_return = '';

        if (is_array($_rulstr)) {
            $_rulstr = isset($_rulstr[1]) ? $_rulstr[1] : '';
        }

        if (!$num || !$_rulstr) {
            return '';
        }

        if ($_rules == 0 && $num % 2 == 0) {
            $_return = " class=\"{$_rulstr}\"";
        } elseif ($_rules == 1 && $num % 2 != 0) {
            $_return = " class=\"{$_rulstr}\"";
        } elseif (is_array($_rules)) {
            if (in_array($num, $_rules)) {
                $_return = " class=\"{$_rulstr}\"";
            }
        } elseif (in_array($num, explode('|', $_rules))) {
            $_return = " class=\"{$_rulstr}\"";
        }
        return $_return;
    }

    /**
     * @desc set 方法集
     */
    function setName($_name) {
        self::$m_name = $_name;
        return $this;
    }

    function setPREFIX($_name) {
        self::$_PREFIX = $_name;
        return $this;
    }

    function setTitle($_title) {
        self::$m_title = $_title;
        return $this;
    }

    function setData($_data) {
        self::$m_data = $_data;
        return $this;
    }

    function setPK($_str) {
        self::$m_PK = $_str;
        return $this;
    }

    function setControl($_control) {
        self::$m_control = $_control;
        return $this;
    }

    function setTags($_tags) {
        self::$m_tags = $_tags;
        return $this;
    }

    function setSymbol($_symbol) {
        self::$m_symbol = $_symbol;
        return $this;
    }

    function setEscape($_name) {
        self::$m_escape = $_name;
        return $this;
    }

    function setValidAct($arr = null) {
        if (!empty($arr) && is_array($arr)) {
            $arr = array_map('strtolower', $arr);
            self::$m_valid_act = array_merge(self::$m_valid_act, $arr);
        } else if (!empty($arr)) {
            array_push(self::$m_valid_act, strtolower($arr));
        }
        return $this;
    }

    static function getPREFIX() {
        return self::$_PREFIX;
    }

    /**
     * @desc 生成select控件头
     */
    static private function doSelectCaput($_name = 'default', $_css = null) {
        return $_css ? "<select name=\"{$_name}\" id=\"{$_name}\" class=\"{$_css}\">" : "<select name=\"{$_name}\" id=\"{$_name}\">";
    }

    /**
     * @desc 生成select控件尾
     */
    static private function doSelectTail() {
        return '</select>';
    }

    /**
     * @desc 生成select option选项
     */
    static private function doSelect($_option = null, $_val = '0') {
        $_str = '';
        if (!$_option)
            $_option = array('请选择');

        foreach ($_option as $key => $value) {
            $_str .= $_val == $key ? "<option value=\"{$key}\" selected=\"selected\">{$value}</option>" : "<option value=\"{$key}\">{$value}</option>";
        }
        return $_str;
    }

    /**
     * @desc 生成input控件
     */
    static private function doInput($_name = 'default', $_type = 'text', $_val = '', $_css = null, $_check = false, $isreadyonly = false) {
        $_str = '';
        $_id = $_name;
        in_array($_type, array('radio', 'checkbox')) ? $_name = "{$_name}[]" : '';
        $_str .= $_css ? "<input name=\"{$_name}\" " . (in_array($_type, array('radio', 'checkbox')) ? '' : 'id="' . $_id . '" ') . "type=\"{$_type}\" value=\"{$_val}\" class=\"{$_css}\" " : "<input name=\"{$_name}\" " . (in_array($_type, array('radio', 'checkbox')) ? '' : 'id="' . $_id . '" ') . "type=\"{$_type}\" value=\"{$_val}\" ";
        $_str .= $isreadyonly ? 'readonly="readonly" ' : '';
        $_str .= (in_array($_type, array('radio', 'checkbox')) && $_check) ? 'checked="checked" />' : '/>';
        return $_str;
    }
    
    /**
     * @desc 生成img控件html
     */
    static private function doImg($_name = 'default', $_src = null, $_alt = null, $_css = null) {
        $_id = $_name;
        $_str = "<img id=\"{$_id}\" src=\"{$_src}\" ";
        $_str .= $_alt ? "alt=\"{$_alt}\" " : '';
        $_str .= $_css ? "class=\"{$_css}\" />" : '/>';
        return $_str;
    }    

    /**
     * @desc 生成textarea控件
     */
    static private function doTextarea($_name = 'default', $_val = '', $_css = null) {
        return $_css ? "<textarea name=\"{$_name}\" id=\"{$_name}\" class=\"{$_css}\">{$_val}</textarea>" : "<textarea name=\"{$_name}\" id=\"{$_name}\">{$_val}</textarea>";
    }

    /**
     * @desc 执行字段值转义
     */
    static private function doEscape() {
        $_ret = false;
        if (self::$m_escape && is_array(self::$m_escape)) {
            foreach (self::$m_escape as $key => $val) {
                if (self::$m_PK == $key) {
                    continue;
                } elseif (isset($val['field']) && isset($val['keys']) && is_array($val['field']) && is_array($val['keys'])) {
                    foreach (self::$m_data as $mkey => $mval) {
                        $_tmp = array();
                        foreach (explode(self::$m_symbol, $mval[$key]) as $v) {
                            $_tmpes = explode('|', $v);
                            if (!isset($_tmpes[1])) {
                                $_tmpes[1] = implode('#', array_keys($val['field']));
                            }

                            $_tmp_ = array();
                            $_keyes = $val['keys'][$_tmpes[0]];
                            foreach (explode('#', $_tmpes[1]) as $vls) {
                                $_tmp_[] = isset($val['field'][$vls]) ? $val['field'][$vls] : $vls;
                            }

                            $_tmp[] = $_keyes . '|' . implode('|', $_tmp_);
                        }

                        self::$m_data[$mkey][$key] = implode(self::$m_symbol, $_tmp);
                    }
                } else {
                    foreach (self::$m_data as $mkey => $mval) {
                        // 判断是否需要转义
                        if (isset($mval[$key])) {
                            $_tmp = array();  
                            $_tmp_arr = is_array($mval[$key]) ? implode(self::$m_symbol, $mval[$key]) : $mval[$key];
                            foreach (explode(self::$m_symbol, $_tmp_arr) as $v) {
                                $_tmp[] = isset($val[$v]) ? $val[$v] : $v;
                            }
                            self::$m_data[$mkey][$key] = implode(self::$m_symbol, $_tmp);
                        }
                    }
                }
            }
            $_ret = true;
        }
        
        return $_ret;
    }

    /**
     * @desc 控件参数拆分
     */
    static private function doParse($fileds, $type = 'input', $attr = 'text', $value = null, $css = null, $prefix = true) {
        $_parse = array('0' => $prefix && true ? self::$_PREFIX . $fileds : $fileds);
        switch ($type) {
            case 'img':
            case 'input':
                foreach (array(1, 3, 4, 5) as $e => $u) {
                    if ($u > 1) {
                        $_parse[$u] = isset(self::$m_title[$fileds][$u+1]) ? self::$m_title[$fileds][$u+1] : '';
                    }
                    
                    if ($u == 1 && empty($_parse[$u]) && $attr) $_parse[$u] = $attr;
                    if ($u == 3 && empty($_parse[$u]) && $css) $_parse[$u] = $css;
                }
                
                if (in_array($attr, array('radio', 'checkbox'))) {
                    $_parse[2] = $value;
                    if (isset(self::$m_data[$fileds]) && self::$m_data[$fileds] && in_array((string) $value, is_array(self::$m_data[$fileds]) ? self::$m_data[$fileds] : explode(self::$m_symbol, self::$m_data[$fileds]))) {
                        $_parse[4] = true;
                    } else {
                        $_parse[4] = false;
                    }
                } else {
                    $_parse[2] = isset(self::$m_data[$fileds]) ? self::$m_data[$fileds] : (isset(self::$m_title[$fileds][3]) ? self::$m_title[$fileds][3] : $value);
                }
                //dump($_parse, 'input:');
                break;

            case 'textarea':
                foreach (array(3) as $e => $u) {
                    isset(self::$m_title[$fileds][$u]) ? $_parse[$u] = self::$m_title[$fileds][$u] : $_parse[$u] = '';
                }
                $_parse[1] = isset(self::$m_data[$fileds]) ? self::$m_data[$fileds] : '';
                break;

            case 'select':
                $_parse = array();
                $_parse[2] = is_array($value) ? null : array($value => isset(self::$m_title[$fileds][2]['vlist'][$value]) ? self::$m_title[$fileds][2]['vlist'][$value] : '');
                
                // 初始值
                $_parse[3] = isset(self::$m_data[$fileds]) ? self::$m_data[$fileds] : (isset(self::$m_title[$fileds][3]) ? self::$m_title[$fileds][3] : '');
                break;
        }
        ksort($_parse);
        //dump($_parse, '$_parse::');
        
        return $_parse;
    }

    static private function doParseByFunc($parse, $_vals) {
        $_tmp_h = '';
        (!isset($parse['pref'])) ? $parse['pref'] = '#' : '';
        (!isset($parse['name'])) ? $parse['name'] = '无' : '';
        $parse['target'] = (isset($parse['target'])) ? ' target="' . $parse['target'] . '"' : '';
        $parse['class'] = (isset($parse['class'])) ? ' class="' . $parse['class'] . '"' : '';
        (!isset($parse['parse'])) ? $parse['parse'] = array() : '';
        $_tmp_h .= '|&nbsp;<a ';

        if (isset($parse['function']) && is_array($parse['function'])) {
            $_tmp_h .= self::splitParseByFunc($parse, $_vals);
        } else {
            $_tmp_h .= "href=\"{$parse['pref']}";
            $_tmp_h .= self::splitParse($parse, $_vals);
            $_tmp_h .= '"';
        }
        $_tmp_h .= "{$parse['class']}{$parse['target']}>{$parse['name']}</a>";
        return $_tmp_h;
    }

    // 取绑定事件 array('act'=>'onclick', 'func'=>'alert', 'parse'=>array(), 'rules'=>array('行数1|列数1', '行数2|列数2')||'行数1|列数1,行数2|列数2') 此处行数可用*表示所有行 规则为空则表示全部
    static private function getFuncByList() {
        $_nums = func_num_args();

        if ($_nums === 3)
            list($func, $val, $rows) = func_get_args();
        if ($_nums === 4)
            list($func, $val, $rows, $cols) = func_get_args();

        if ($_nums < 3)
            return;
        if (!isset($func['act']) || !in_array(strtolower($func['act']), self::$m_valid_act) || (count($func) < 2))
            return;

        if (!isset($cols))
            $cols = '';

        // 取参数
        $_parse_str = self::getFuncByListParses($func, $val);

        // 取函数名
        $_func_name = self::getFuncName($func);

        // 校验规则
        $_rules = self::checkRulesByBool($func, $rows, $cols);

        $_func_str = (strtolower($_func_name) == 'function' || empty($_func_name)) ? (isset($func['func']) ? $func['func'] : '0') : $_func_name . '(' . $_parse_str . ')';

        return $_rules ? ' ' . $func['act'] . '="javascript:void(' . $_func_str . ');"' : '';
    }

    // 校验规则
    static private function checkRulesByBool($parse = null, $ro = null, $co = null) {
        if (isset($parse['rules'])) {
            $_rules = false;
            $_intersect = array();
            $_rules_arr = is_array($parse['rules']) ? $parse['rules'] : explode(',', $parse['rules']);
            if (!empty($ro) && empty($co))
                array_push($_intersect, $ro, $ro . '|*');
            if (!empty($co) && empty($ro))
                array_push($_intersect, $co, '*|' . $co);
            if (!empty($ro) && !empty($co))
                array_push($_intersect, $ro, $ro . '|*', $co, '*|' . $co, $ro . '|' . $co);

            $tmp = array_intersect($_intersect, $_rules_arr);
            if (!empty($tmp))
                $_rules = true;
        }
        return isset($_rules) ? $_rules : true;
    }

    // 取函数名
    static private function getFuncName($parse = null) {
        if (!empty($parse['func'])) {
            //list($_func_name, ) = explode('(', $parse['func']);
            $_func_name = $parse['func'];
        }
        return isset($_func_name) ? trim($_func_name) : '';
    }

    // 取参数
    static private function getFuncByListParses($parse = null, $vals = null) {
        if (empty($parse) || empty($vals))
            return;
        if (isset($parse['parse'])) {
            $_parse_str = array();
            $_parse = !is_array($parse['parse']) ? explode(',', $parse['parse']) : $parse['parse'];
            foreach ($_parse as $v) {
                $_parse_str[] = isset($vals[$v]) ? $vals[$v] : $v;
            }
            $_parse_str = '\'' . implode('\',\'', $_parse_str) . '\'';
            //$_parse_str = implode(',', $_parse_str);                
        }

        return isset($_parse_str) ? $_parse_str : '';
    }

    static private function splitParse($parse = array(), $_vals) {
        $_tmp_f = '';
        foreach ($parse['parse'] as $key => $val) {
            $keys = is_numeric($key) ? 'items' . $key : $key;
            $values = isset($parse['data'][$_vals[self::$m_PK]][$val]) ? $parse['data'][$_vals[self::$m_PK]][$val] : (isset($_vals[$val]) ? $_vals[$val] : $val);
            $_tmp_f .= '&' . $keys . '=' . $values;
        }

        if (!count($parse)) {
            $_tmp_f .= '&id=' . $_vals[self::$m_PK];
        }

        if (isset(self::$m_control['control']['parse_mode']) && self::$m_control['control']['parse_mode'] == 'rewrite') {
            $_tmp_f = strtr($_tmp_f, array('&' => '/', '=' => '/'));
        }

        return $_tmp_f;
    }

    static private function splitParseByFunc(array $parse, $_vals) {
        $_tmp_c = '';
        if (isset($parse['function']['click']) && $parse['function']['click']) { // 向上兼容click
            $_tmp_c .= "onclick=\"javascript:void({$parse['function']['func']}(";
        } else if (isset($parse['function']['act']) && in_array($parse['function']['act'], self::$m_valid_act)) {
            $_tmp_c .= "{$parse['function']['act']}=\"javascript:void({$parse['function']['func']}(";
        } else {
            $_tmp_c .= "href=\"javascript:void({$parse['function']['func']}(";
        }

        $_tmp_m = '';
        $_parse = isset($parse['function']['parse']) ? $parse['function']['parse'] : $parse['parse'];
        foreach ($_parse as $val) {
            if (isset($parse['data'][$_vals[self::$m_PK]][$val])) {
                $_tmp_m .= ',\'' . $parse['data'][$_vals[self::$m_PK]][$val] . '\'';
            } else if (isset($_vals[$val])) {
                $_tmp_m .= ',\'' . $_vals[$val] . '\'';
            } else {
                $_tmp_m .= in_array(msubstr($val, 0, 1), array('$', '{', '<')) ? (',' . $val) : (',\'' . quotes($val) . '\'');
            }
        }
        $_tmp_c .= substr($_tmp_m, 1) . "));\"";

        if (isset($parse['function']['click']) && $parse['function']['click'] || (isset($parse['function']['act']) && in_array($parse['function']['act'], self::$m_valid_act))) {
            $_tmp_c .= isset($parse['control']['parse_mode']) && $parse['control']['parse_mode'] == 'rewrite' ? " href=\"{$parse['pref']}" . '/id/' . $_vals[self::$m_PK] . '"' : " href=\"{$parse['pref']}" . '&id=' . $_vals[self::$m_PK] . '"';
        }

        return $_tmp_c;
    }
}
?>