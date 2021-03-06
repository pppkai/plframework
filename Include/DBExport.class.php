<?php

/**
 +------------------------------------------------------------------------------
 * 数据库备份导出操作类
 +------------------------------------------------------------------------------
 * @category PLFrame
 * @package  Include
 * @author   pengzl<pengzl_gz@163.com>
 * @version  $Id: DBExport.class.php 2 2015-11-02 02:28:48Z pengzl $
 +------------------------------------------------------------------------------
 */
class DBExport {

    private static $instance = null;
    private static $_DBInstance = null;
    private static $m_eol = PHP_EOL;

    private function __construct() {
        if (!is_object(self::$_DBInstance)) {
            self::$_DBInstance = db::getInstance();
        }
    }

    /**
     * @desc 创建单例对象
     */
    public static function getInstance() {
        if (!is_object(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 生成JSON格式字串  
     * $cons说明 
     * 空：返回全部记录 
     * array：eg. array('id'=>'1') 返回id=1的记录 
     */
    private static function countNum($table, $start = '0', $limit = '10', $cons = '') {
        if (!is_object(self::$_DBInstance)) {
            self::$_DBInstance = db::getInstance();
        }
        $Result = self::$_DBInstance->select($table, $cons);
        $totalNum = count($Result);

        $cons['limit'] = "{$start},{$limit}";
        $result = self::$_DBInstance->select($table, $cons);
        $resultNum = count($result); // 当前结果数 

        return array('totalNum' => $totalNum, 'resultNum' => $resultNum, 'result' => $result);
    }

    /**
     * 生成JSON格式字串  
     * $cons说明 
     * 空：返回全部记录 
     * array：eg. array('where'=>array('id'=>'1')) 返回id=1的记录 
     */
    public static function toExtJson($table, $star = '0', $limit = '10', $cons = '') {
        $_arr = self::countNum($table, $star, $limit, $cons);
        $str = '';
        $str .= '{';
        $str .= "'totalCount':'{$_arr['totalNum']}',";
        $str .= "'rows':";
        $str .= '[';
        $_strs = '';

        foreach (range(0, $_arr['resultNum'] - 1) as $i) {
            $_strs .= ",{";
            $count = count($_arr['result'][$i]);
            $j = 1;
            $_str = '';

            foreach ($_arr['result'][$i] as $key => $val) {
                if ($j <= $count) {
                    $_str .= ",'" . $key . "':'" . $val . "'";
                }

                $j++;
            }

            $_str = substr($_str, 1);

            $_strs .= $_str;
            $_strs .= "}";
        }

        $_strs = substr($_strs, 1);

        $str .= $_strs;
        $str .= ']';
        $str .= '}';
        return $str;
    }

    /**
     * 生成XML格式字串  
     * @return xml string 
     */
    public static function toExtXml($table, $start = '0', $limit = '10', $cons = '') {
        $_arr = self::countNum($table, $start, $limit, $cons);
        
        //
        ob_get_clean();
        Header('Content-Type: text/xml');
        $xml = '<?xml version="1.0" encoding="utf-8" ?>' . self::$m_eol;
        $xml .= "<root>" . self::$m_eol;
        $xml .= ' <totalCount>' . $_arr['totalNum'] . "</totalCount>" . self::$m_eol;
        $xml .= " <items>" . self::$m_eol;

        foreach (range(0, $_arr['resultNum'] - 1) as $i) {
            $xml .= '  <item>' . self::$m_eol;

            foreach ($_arr['result'][$i] as $key => $val) {
                $xml .= '   <' . $key . '>' . $val . '</' . $key . ">" . self::$m_eol;
            }

            $xml .= '  </item>' . self::$m_eol;
        }

        $xml .= " </items>" . self::$m_eol;
        $xml .= "</root>" . self::$m_eol;
        return $xml;
    }

    /**
     * 生成下载txt
     * @return void
     */
    public static function toTxt($table = null, $where = null, $conts = null, $eol = null) {
        if (empty($eol))
            $eol = self::$m_eol;
        
        $txt = '';
        if (isset($table, $where) && !isset($conts)) {
            if (!is_object(self::$_DBInstance)) {
                self::$_DBInstance = new db();
            }
            $conts = self::$_DBInstance->select($table, $where, 'getAll');
        }
        foreach ($conts as $vals) {
            $txt .= implode(',', array_values($vals)) . $eol;
        }
        
        // 
        ob_get_clean();
        Header('Pragma: public');
        Header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 86400) . 'GMT');
        Header('Cache-Control: private');
        Header('Cache-Component: must-revalidate, post-check=0, pre-check=0');
        Header('Last-Modified: ' . gmdate('D, d M Y H:i:s', time()) . 'GMT');
        Header('Content-Disposition: attachment; filename=' . rand_string(5, 1) . '.txt');
        Header('Content-Length: ' . mb_strlen($txt));
        Header('Content-type: application/octetstream');
        Header('Content-Encoding: none');
        Header('Content-Transfer-Encoding: binary');
        return $txt;
    }

    /**
     * 输出word表格  
     * @return file 
     */
    public static function toWord($table, array $mapping, $fileName = 'default') {
        $_fields = implode(',', array_keys($mapping));
        $_titles = array_values($mapping);
        
        ob_get_clean();
        //Header('Content-Type: text/html; charset=UTF-8');        
        Header('Content-Type:application/msword');
        Header('Content-Disposition:attachment;filename=' . $fileName . '.doc');
        Header('Cache-Control: private');
        Header('Pragma:no-cache');
        Header('Expires:0');
        $_str = '<html xmlns:v="urn:schemas-microsoft-com:vml"
                xmlns:o="urn:schemas-microsoft-com:office:office"
                xmlns:w="urn:schemas-microsoft-com:office:word\"
                xmlns:m="http://schemas.microsoft.com/office/2004/12/omml"
                xmlns="http://www.w3.org/TR/REC-html40"> 
                <head> 
                <meta http-equiv="Content-Type" content="text/html; charset=' . C('OUTPUT_CHARSET') . '" /> 
                <meta name=ProgId content=Word.Document>
                <meta name=Generator content="Microsoft Word 12">
                <meta name=Originator content="Microsoft Word 12">
                <title>' . $fileName . '</title> 
                </head> 
                <body>';

        $_str .= '<table border=1><tr>';

        foreach ($_titles as $key => $val) {
            $_str .= '<td>' . $val . '</td>';
        }

        $_str .= '</tr>';
        
        if (!is_object(self::$_DBInstance)) {
            self::$_DBInstance = db::getInstance();
        }
        $results = is_array($table) ? $table : self::$_DBInstance->getAll("select {$_fields} from " . $table);

        foreach ($results as $result) {
            $_str .= '<tr>';
            foreach ($result as $key => $val) {
                $_str .= '<td>' . $val . '</td>';
            }
            $_str .= '</tr>';
        }

        $_str .= '</table>';
        $_str .= '</body>';
        $_str .= '</html>';
        return $_str;
    }

    /**
     * 输出 Excel 表格  
     * @return file 
     */
    public static function toExcel($table, array $mapping, $fileName = 'default') {
        $_fields = implode(',', array_keys($mapping));
        $_titles = array_values($mapping);
        
        ob_get_clean();
        //Header("Content-Type: text/html; charset=UTF-8");
        Header('Content-type:application/octet-stream');
        Header('Accept-Ranges:bytes');
        Header('Content-type:application/vnd.ms-excel');
        Header('Content-Disposition:attachment;filename=' . $fileName . '.xls');
        Header('Cache-Control: private');
        Header('Pragma:no-cache');
        Header('Expires:0');

        $_str = '
            <html xmlns:v="urn:schemas-microsoft-com:vml"
            xmlns:o="urn:schemas-microsoft-com:office:office"
            xmlns:x="urn:schemas-microsoft-com:office:excel"
            xmlns="http://www.w3.org/TR/REC-html40">
            <head> 
            <meta http-equiv=Content-Type content="text/html; charset=' . C('OUTPUT_CHARSET') . '">
            <meta name=Generator content="Microsoft Excel 12">
            </head> 
            <body>';
        $_str .= '<table width="100%" border="0" cellspacing="0" cellpadding="0"><tr>';

        foreach ($_titles as $key => $val) {
            $_str .= '<td>' . $val . '</td>';
        }

        $_str .= '</tr>';
        
        if (!is_object(self::$_DBInstance)) {
            self::$_DBInstance = db::getInstance();
        }
        $results = is_array($table) ? $table : self::$_DBInstance->getAll("select {$_fields} from " . $table);

        foreach ($results as $result) {
            $_str .= '<tr>';
            foreach ($result as $key => $val) {
                $_str .= '<td'.(is_numeric($val)?' style="vnd.ms-excel.numberformat:@"':'').'>' . $val . '</td>';
            }
            $_str .= '</tr>';
        }

        $_str .= '</table>';
        $_str .= '</body>';
        $_str .= '</html>';
        return $_str;
    }

    /**
     * 输出 Excel 表格 扩展  
     * @return string 
     */
    public static function toExcel_Ex($data, array $mapping, $fileName = 'default') {
        return self::toExcel($data, $mapping, $fileName);
    }

    /**
     * 导出成指定文件名的SQL文件  
     * @return string 
     */
    public static function backup2file($table, $fields = array(), $where = array(), $file = 'default') {
        ob_get_clean();
        Header('Content-disposition: filename=' . $file . '.sql'); //所保存的文件名 
        Header('Content-type: application/octetstream');
        Header('Cache-Control: private');
        Header('Pragma: no-cache');
        Header('Expires: 0');
        return self::Backup($table, $fields, $where);
    }

    /**
     * 生成单表或多表的INSERT语句  
     * @return string 
     */
    public static function Backup($table, $fields = array(), $where = array()) {
        $str = null;
        !is_array($table) ? $table = array($table) : '';

        foreach ($table as $key => $tab) {
            $_tab = array($key => $tab);
            $str .= (isset($fields[$tab]) && isset($where[$tab])) ? self::getTable($_tab, $fields[$tab], $where[$tab]) : ((isset($fields[$tab]) && !isset($where[$tab])) ? self::getTable($_tab, $fields[$tab]) : ((!isset($fields[$tab]) && isset($where[$tab])) ? self::getTable($_tab, '', $where[$tab]) : self::getTable($_tab)));
        }

        return $str;
    }

    /**
     * 导出直接写文件 file可带路径  
     * @return file 
     */
    public static function backupToFile($table, $fields = array(), $where = array(), $file = '') {
        $content = self::Backup($table, $fields, $where);
        $_rtn = file_put_contents($file ? $file : ROOT . '/default.sql', $content);
        chmod($file, 0777);
        return $_rtn;
    }

    /**
     * 读取文件  
     * @return string 
     */
    public static function getFile($file = 'default.sql') {
        //return (is_file($file)) ? iconv('GB2312', 'UTF-8//IGNORE', trim(file_get_contents($file))) : false; 
        return (is_file($file)) ? trim(file_get_contents($file)) : false;
    }

    /**
     * 清空表  
     * @return false/true 
     */
    public static function truncate($table) {
        if (!is_object(self::$_DBInstance)) {
            self::$_DBInstance = db::getInstance();
        }        
        return $table ? self::$_DBInstance->execute("TRUNCATE TABLE $table") : false;
    }

    /**
     * 分解字段名  
     * @return string 
     */
    static private function getKV($_fields = array()) {
        $field = '(';
        $fields = '';
        foreach ($_fields as $key => $val) {
            if (is_string($key)) {
                $fields .= ",`{$key}`";
            } else {
                $fields .= ",`{$val}`";
            }
        }
        $fields = substr($fields, 1);
        $field .= $fields . ')';
        return (!$fields) ? '' : $field;
    }

    /**
     * 生成指定表的INSERT语句  
     * @return string 
     */
    public static function getTable($table, $field = null, $where = null) {
        $_fields = is_array($field) ? self::getKV($field) : '';

        if (!empty($field) && is_array($field)) {
            $field = '`' . implode('`,`', $field) . '`';
        } else {
            $field = '*';
        }

        if (is_string(key($table))) {
            $tables = key($table);
        } else {
            $tables = $table[key($table)];
        }
        $table = $table[key($table)];
        
        if (!is_object(self::$_DBInstance)) {
            self::$_DBInstance = db::getInstance();
        }
        $results = $where ? self::$_DBInstance->getAll("select {$field} from {$table} where {$where}") : self::$_DBInstance->getAll("select {$field} from {$table}");
        $temp = '';

        foreach ($results as $result) {
            $_insertmp = "INSERT INTO  {$tables} {$_fields} VALUES (";
            $_insert = '';

            foreach ($result as $key => $val) {
                if ($val != '') {
                    $_insert .= ", '" . addslashes($val) . "'";
                } else {
                    $_insert .= ", ''";
                }
            }

            $_insert = substr($_insert, 1);
            $_insertmp .= "{$_insert});" . self::$m_eol;
            $temp = $temp . $_insertmp;
        }
        return $temp;
    }

    /**
     * 批量执行单表INSERT操作  
     * @return true/false 
     */
    public static function doInsert($table, $sql_str = '', $isClear = true) {
        if (!$table || !$sql_str) {
            return false;
        }
        if (!is_object(self::$_DBInstance)) {
            self::$_DBInstance = db::getInstance();
        }
        $_tmpStatus = self::$_DBInstance->getTablesStatus($table);

        if ($_tmpStatus['Engine'] != 'InnoDB') {
            self::$_DBInstance->execute("ALTER TABLE {$table} ENGINE = 'InnoDB'");
        }

        self::splitMySqlFile($res, $sql_str);

        self::$_DBInstance->begin();

        if (strlen(self::getTable(array($table))) && $isClear) {
            self::truncate($table);
        }

        foreach ($res as $val) {
            if (!self::$_DBInstance->execute(trim($val))) {
                self::$_DBInstance->rollback();
                return false;
            }
        }

        self::$_DBInstance->commit();
        self::$_DBInstance->execute("ALTER TABLE {$table} ENGINE = '{$_tmpStatus['Engine']}'");

        return true;
    }

    /**
     * 批量执行INSERT操作 不分表名 
     * @return true/false 
     */
    public static function doMultiInsert($sql_str = '') {
        if (!$sql_str) {
            return false;
        }
        
        if (!is_object(self::$_DBInstance)) {
            self::$_DBInstance = db::getInstance();
        }        

        foreach (self::splitsql($sql_str) as $val) {
            $sqlstr = self::syntablestruct(trim($val));

            if (!(strpos(trim(substr($sqlstr, 0, 18)), 'CREATE TABLE') === FALSE)) {
                $_tmp = explode('`', $sqlstr, 3);
                self::$_DBInstance->execute("DROP TABLE IF EXISTS `{$_tmp[1]}`");
            }

            if (!(strpos(trim(substr($sqlstr, 0, 18)), 'COMMIT') === FALSE)) {
                continue;
            }

            if (!self::$_DBInstance->execute($sqlstr)) {
                return false;
            }
        }
        return true;
    }

    /**
     * 从标准SQL文件恢复数据  
     * @return false/true 
     */
    public static function RestoreFromFile($file = 'default.sql', $table = '', $isClear = true) {
        if (!$file) {
            $file = 'default.sql';
        }

        if (is_file($file) && $table) {
            return self::doInsert($table, self::getFile($file), $isClear);
        } elseif (is_file($file) && !$table) {
            return self::doMultiInsert(self::getFile($file));
        }
        return false;
    }

    /**
     * 从SQL语句恢复数据  
     * @return true/false 
     */
    public static function RestoreFromContent($table, $content, $isClear = true) {
        if ($table && $content) {
            return self::doInsert($table, trim($content), $isClear);
        } elseif ($content && !$table) {
            return self::doMultiInsert(trim($content), $isClear);
        }
        return false;
    }

    /**
     * 拆分SQL语句  
     * @return array 
     */
    private static function splitMySqlFile(&$ret, $sql) {
        $sql = trim($sql);
        $sql = split(';', $sql);
        $arr = array();

        foreach ($sql as $sq) {
            if ($sq != "") {
                $arr[] = $sq;
            }
        }
        $ret = $arr;
        return true;
    }

    /**
     * 拆分SQL语句  
     * @return array 
     */
    private static function splitsql($sql) {
        $sql = str_replace("\r", self::$m_eol, $sql);
        $ret = array();
        $num = 0;
        $queriesarray = explode(";" . self::$m_eol, trim($sql));
        unset($sql);

        foreach ($queriesarray as $query) {
            $queries = explode(self::$m_eol, trim($query));
            $ret[$num] = '';
            foreach ($queries as $query) {
                $ret[$num] .= (isset($query[0]) && $query[0] == '#') ? NULL : $query;
            }
            $num++;
        }
        return($ret);
    }

    /**
     * 拼装成SQL执行语句  
     * @return array 
     */
    private static function syntablestruct($sql = '', $version = '', $dbcharset = '') {
        if (strpos(trim(substr($sql, 0, 18)), 'CREATE TABLE') === FALSE) {
            return $sql;
        }

        if (substr(trim($sql), 0, 9) == 'SET NAMES' && !$version) {
            return '';
        }

        $sqlversion = strpos($sql, 'ENGINE=') === FALSE ? FALSE : TRUE;

        if ($sqlversion === $version) {
            return $sqlversion && $dbcharset ? preg_replace(array('/ character set \w+/i', '/ collate \w+/i', "/DEFAULT CHARSET=\w+/is"), array('', '', "DEFAULT CHARSET=$dbcharset"), $sql) : $sql;
        }

        if ($version) {
            return preg_replace(
                    array('/TYPE=HEAP/i', '/TYPE=(\w+)/is'), array("ENGINE=MEMORY DEFAULT CHARSET=$dbcharset", "ENGINE=\\1 DEFAULT CHARSET=$dbcharset"), $sql
            );
        } else {
            return preg_replace(
                    array('/character set \w+/i', '/collate \w+/i', '/ENGINE=MEMORY/i', '/\s*DEFAULT CHARSET=\w+/is', '/\s*COLLATE=\w+/is', '/ENGINE=(\w+)(.*)/is'), array('', '', 'ENGINE=HEAP', '', '', 'TYPE=\\1\\2'), $sql
            );
        }
    }
}
?>