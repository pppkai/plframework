<?php

/**
  +------------------------------------------------------------------------------
 * 数据操作中间层类
  +------------------------------------------------------------------------------
 * @category lib
 * @package  DB
 * @author   pengzl<pengzl_gz@163.com>
 * @version  $Id: db.class.php 17 2016-10-13 09:33:56Z pengzl $
  +------------------------------------------------------------------------------
 */
class db {

    //当前类对象
    private static $instance = null;
    
    //定义默认数据库主机名
    private static $dbhost = 'localhost';
    
    //定义默认数据库主机端口
    private static $dbport = 3306;
    
    //定义默认数据库名
    private static $dbname = '';
    
    //定义默认数据库用户名
    private static $dbuser = 'root';
    
    //定义默认数据库密码
    private static $dbpass = '';
    
    //当前库操作类对象
    private static $stmt = null;
    
    //数据库操作类对象
    private static $DB = null;
    
    //是否调试模式
    private static $debug = 0;
    
    //配置参数
    private static $configs = null;
    
    //数据库表达式 
    protected static $exp = array('eq' => '=', 'neq' => '!=', 'gt' => '>', 'egt' => '>=', 'lt' => '<', 'elt' => '<=', 'in' => ' IN ', 'notin' => ' NOT IN ', 'like' => ' LIKE ', 'notlike' => ' NOT LIKE ');

    public function __construct($conf = null) {
        if (empty(self::$configs) || !empty($conf)) {
            $_configs_url = is_string($conf) && $conf ? $conf : dirname(__FILE__) . '/db.config.php';

            if (is_array($conf)) {
                self::$configs = $conf;

                // 参数变化重连MYSQL
                if (is_object(self::$DB))
                    self::$DB = null;
            } elseif (is_file($_configs_url)) {
                self::$configs = require_once($_configs_url);
            } else {
                throw new Exception('配置文件不存在或路径出错');
            }
        }

        is_object(self::$DB) || self::connect();
        empty(self::$configs['charset']) && self::$configs['charset'] = 'utf8';
        empty(self::$debug) && self::$debug = true;

        if (!self::$DB->set_charset(self::$configs['charset'])) {
            throw new Exception('无法将数据库查询的字符集设置为' . self::$configs['charset'] . ',<br />原因:' . self::$DB->error);
        }
    }

    /**
     * @desc 创建单例对象
     */
    public static function getInstance($conf = null) {
        if (!is_object(self::$instance))
            self::$instance = new self($conf);
        return self::$instance;
    }

    /**
     * @desc 析构
     */
    public function __destruct() {
        self::ClearParse();
    }

    /**
     * @desc 禁止克隆
     */
    private function __clone() {}

    /**
     * @desc 连接数据库
     */
    static private function connect() {
        if (!isset(self::$DB) || !is_object(self::$DB)) {
            $mysqli = new mysqli(
                self::$configs['dbhost'], self::$configs['dbuser'], self::$configs['dbpass'], self::$configs['dbname'], self::$configs['dbport']
            );

            if ($mysqli->connect_errno || $mysqli->connect_errno === NULL) {
                throw new Exception('数据库连接出错:' . $mysqli->connect_error);
            }

            self::$DB = &$mysqli;
        }

        return self::$DB;
    }

    /**
     * @desc execute query
     */
    public function _query($sql) {
        if (false === $result = self::$DB->query($sql)) {
            throw new Exception('查询语句出错' . self::$DB->error . ' , SQL: ' . $sql);
        }
        
        return $result;
    }

    /**
     * @desc 获取当前库的所有表名
     */
    public function getTablesName() {
        self::$stmt = self::_query('SHOW TABLES');
        $rows = array();

        while (null !== ($result = self::$stmt->fetch_assoc())) {
            $rows[] = $result;
        }

        self::$stmt = null;
        return $rows;
    }

    /**
     * @desc 获取某库某表状态值
     */
    public function getTablesStatus($table = '') {
        self::$stmt = self::_query("SHOW TABLE STATUS FROM " . C("DB_NAME"));
        $rows = array();

        while (null !== ($result = self::$stmt->fetch_assoc())) {
            $rows[$result['Name']] = $result;
        }

        self::$stmt = null;

        return $table ? $rows[$table] : $rows;
    }

    /**
     * @desc 获取数据表里的字段
     */
    public function getFields($table) {
        self::$stmt = self::_query("DESCRIBE $table");
        $rows = array();

        while (null !== ($result = self::$stmt->fetch_assoc())) {
            $rows[] = $result;
        }

        self::$stmt = null;
        return $rows;
    }

    /**
     * @desc 获取所有数据
     */
    public function getAll($sql, $type = "fetch_assoc") {
        if (false == (self::$stmt = self::_query($sql))) {
            return array();
        }

        $rows = array();
        while (null !== ($row = self::$stmt->$type())) {
            $rows[] = $row;
        }

        self::$stmt = null;
        return $rows;
    }

    /**
     * @desc 获取单行数据
     */
    public function getOne($sql, $type = "fetch_assoc") {
        $row = false;
        if (false === stripos($sql, 'limit'))
            $sql .= ' LIMIT 1';
        
        self::$stmt = self::_query($sql);
        if (self::$stmt === false || !($row = self::$stmt->$type()))
            return array();

        self::$stmt = null;
        return $row;
    }

    /**
     * @desc 获取某列值
     * @return string
     */
    public function getCell($col, $sql) {
        $row = self::getOne($sql);
        return ($row === array() || !isset($row[$col])) ? null : $row[$col];
    }
    
    /**
     * @desc 获取某列值
     * @return string
     */
    public function getCounts() {
        return self::getCell('totalNum', 'SELECT FOUND_ROWS() AS totalNum');
    }    

    /**
     * @desc 执行 select 语句
     * @param table 表
     * @param avgs  参数
     * @see   formatSQL
     * @return array
     */
    public function select($table, $avgs = NULL, $method = 'getAll') {
        if (!is_array($avgs))
            return false;

        $sql = self::formatSQL('select', $table, $avgs);

        $result = $this->$method($sql);
        return $result;
    }

    /**
     * @desc 拼装 insert update delete 语句
     */
    public function insert($table, $avgs, $into = false) {
        return self::runUpdateSQL($into ? 'insert_into' : 'insert', $table, $avgs);
    }

    public function update($table, $avgs) {
        return self::runUpdateSQL('update', $table, $avgs);
    }

    public function delete($table, $avgs) {
        return self::runUpdateSQL('delete', $table, $avgs);
    }

    private function runUpdateSQL($mode, $table, $avgs) {
        $sql = self::formatSQL($mode, $table, $avgs);
        $rows_aff = self::execute($sql);

        if ($mode === 'insert')
            return self::last_id();
        return $rows_aff;
    }

    /**
     * 执行 insert update delete SQL 语句
     * @param sql
     */
    public function execute($sql) {
        if ($sql == false || empty($sql)) {
            throw new Exception('SQL语句为空');
            return false;
        }

        self::$stmt = self::_query($sql);
        return self::affected_rows();
    }

    /**
     * @desc 事务开始
     * @return void
     */
    public function begin() {
        self::$DB->autocommit(false);
    }

    /**
     * @desc 事务结束
     * @return void
     */
    public function commit() {
        self::$DB->commit();
        self::$DB->autocommit(true);
    }

    /**
     * @desc 事务回滚
     * @return void
     */
    public function rollback() {
        self::$DB->rollback();
    }

    /**
     * @desc 返回最近一次insert产生的id
     * @return int
     */
    public function last_id() {
        return self::$DB->insert_id;
    }

    /**
     * @desc 受影响的记录数
     * @return int
     */
    public function affected_rows() {
        return self::$DB->affected_rows;
    }

    /**
     * @desc 格式化 SQL 语句
     * @param mode  模式 (SELECT | INSERT | INSERT_INTO | UPDATE | DELETE)
     * @param table 表
     * @param avgs  参数
     *              keys:Array|String (for:SELECT),
     *              value:Array (for:INSERT, INSERT_INTO, UPDATE),
     *              join:Array{[table]{on1,on2}} (for:SELECT),
     *              group:Array|String (for:SELECT),
     *              having:Srting (for:SELECT),
     *              where:String|Array,
     *              order:String (for:SELECT),
     *              limit:String
     *              index:String (for:SELECT) eg: FOR UPDATE || LOCK IN SHARE MODE,
     * @return sql:String
     */
    public function formatSQL($mode, $table, $avgs) {
        if (!is_array($avgs) && empty($avgs))
            return false;

        $mode = strtoupper(trim($mode));
        $sql = $mode;

        switch ($mode) {
            case 'SELECT':
                // keys
                if (isset($avgs['keys']) && !in_array($avgs['keys'], array('*', 'SQL_CALC_FOUND_ROWS *'))) {
                    if (!is_array($avgs['keys']) && !(stripos($avgs['keys'], 'SQL_CALC_FOUND_ROWS') === false)) {
                        $sql .= ' ' . $avgs['keys'];
                    } elseif (is_array($avgs['keys']) && !(stripos(implode(' ', $avgs['keys']), 'SQL_CALC_FOUND_ROWS') === false)) {
                        $sql .= ' ' . implode(',', $avgs['keys']);
                    } else {
                        !is_array($avgs['keys']) ? $this->mkArray($avgs['keys']) : '';
                        $sql .= ' ' . $this->selectK($avgs['keys']);
                    }
                } elseif (isset($avgs['keys']) && !is_array($avgs['keys']) && in_array($avgs['keys'], array('SQL_CALC_FOUND_ROWS *'))) {
                    $sql .= ' SQL_CALC_FOUND_ROWS *';
                } else {
                    $sql .= ' *';
                }

                // from
                $sql .= ' FROM ' . $this->fmtKey($table);

                // join
                if (isset($avgs['join'])) {

                    if (!is_array($avgs['join'])) {
                        $this->mkArray($avgs['join']);
                    }

                    foreach ($avgs['join'] as $key => $item) {
                        $sql .= ' LEFT JOIN ';
                        $sql .= $this->fmtKey($key);
                        $sql .= ' ON ' . $this->fmtKey($item[0]) . '=' . $this->fmtKey($item[1]);
                    }
                }

                // where
                if (isset($avgs['where'])) {
                    if (is_array($avgs['where'])) {
                        $where = trim($this->whereKV($avgs['where']));
                    } else {
                        $where = trim($avgs['where']);
                    }

                    if (!empty($where))
                        $sql .= ' WHERE ' . $where;
                }

                // group
                if (isset($avgs['group'])) {
                    $avgs['group'] = is_array($avgs['group']) ? implode(',', $avgs['group']) : $avgs['group'];
                    $sql .= ' GROUP BY ' . trim($avgs['group']);

                    // having
                    if (isset($avgs['having']))
                        $sql .= ' HAVING ' . trim($avgs['having']);
                }

                // order
                if (isset($avgs['order']))
                    $sql .= ' ORDER BY ' . trim($avgs['order']);

                // limit
                if (isset($avgs['limit']))
                    $sql .= ' LIMIT ' . trim($avgs['limit']);

                // index
                if (isset($avgs['index']))
                    $sql .= ' ' . trim($avgs['index']);
                break;

            case 'UPDATE':
                if (!isset($avgs['where']))
                    return false;
            case 'INSERT':
                if (!isset($avgs['value']) || !is_array($avgs['value']))
                    return false;

                // table
                $sql .= ' ' . $this->fmtKey($table);

                // keys & values
                $sql .= ' SET ' . $this->updateKV($avgs['value']);

                // where
                if ($mode == 'UPDATE') {
                    if (is_array($avgs['where'])) {
                        $sql .= ' WHERE ' . trim($this->whereKV($avgs['where']));
                    } else {
                        $sql .= ' WHERE ' . trim($avgs['where']);
                    }
                }
                break;

            case 'INSERT_INTO':
                if (!isset($avgs['value']) || !is_array($avgs['value'][0]))
                    return false;

                // table
                $sql = str_replace('_', ' ', $sql);
                $sql .= ' ' . $this->fmtKey($table);

                // get keys
                $sql .= ' (' . $this->selectK(array_keys($avgs['value'][0])) . ') VALUES ';

                // get values
                $sql .= $this->insetMoreV($avgs['value']) . ';';
                break;

            case 'DELETE':
                if (!isset($avgs['where']))
                    return false;

                // table
                $sql .= ' FROM ' . $this->fmtKey($table);

                // where
                if (is_array($avgs['where'])) {
                    $sql .= ' WHERE ' . trim($this->whereKV($avgs['where']));
                } else {
                    $sql .= ' WHERE ' . trim($avgs['where']);
                }
                break;

            default:
                return false;
        }

        return $sql;
    }

    /**
     * @desc 转换字符串为数组
     * @param keys 字符串
     */
    private function mkArray(&$keys) {
        if (!is_array($keys)) {
            $keys = explode(',', $keys);
        }
    }

    /**
     * @desc 格式化字段名
     * @param key 字段名
     */
    private function fmtKey($key) {
        $reg = '/([^\(\)\.\s]+)/';
        $key = preg_replace($reg, '`\1`', $key);
        $reg = '/`(COUNT|SUM|AVG|MIN|MAX|FOUND_ROWS|SQL_CALC_FOUND_ROWS)`\(/i';
        $key = preg_replace($reg, '\1(', $key);
        $reg = '/`DISTINCT`/i';
        $key = preg_replace($reg, 'DISTINCT', $key);
        $reg = '/\s+`(AS)`\s+/i';
        $key = preg_replace($reg, ' \1 ', $key);
        return $key;
    }

    /**
     * @desc 生成 select 语句的字段名部分
     * @param keys 字段名数组
     */
    private function selectK($keys) {
        $result = '';
        $keys = array_filter($keys);
        foreach ($keys as $key) {
            $result .= sprintf(",%s", $this->fmtKey(trim($key)));
        }
        return substr($result, 1);
    }

    /**
     * @desc 生成 insert update 更新部分
     * @param values {key=>value}数组
     * @return String
     */
    private function updateKV($values) {
        $result = '';
        foreach ($values as $key => $value) {
            if (is_array($value)) $value = array_toString($value);            
            if (!is_numeric($value) || (is_numeric($value) && substr($value, 0, 1) == '0' && substr($value, 1, 1) != '.') || (is_numeric($value) && (intval($value) != $value))) {
                $result .= sprintf(",%s=%s", $this->fmtKey(strtolower($key)), '\'' . addslashes($value) . '\'');
            } else {
                $result .= sprintf(",%s=%s", $this->fmtKey(strtolower($key)), addslashes($value));
            }
        }
        return substr($result, 1);
    }

    /**
     * @desc 生成 insert more values 
     * @param values {{key=>value}}数组
     * @return String
     */
    private function insetMoreV($values) {
        $result = '';
        foreach ($values as $val) {
            $result .= ',(';
            $re_tmp = '';
            foreach ($val as $value) {
                if (!is_numeric($value) || (is_numeric($value) && substr($value, 0, 1) == '0' && substr($value, 1, 1) != '.') || (is_numeric($value) && (intval($value) != $value))) {
                    $re_tmp .= sprintf(",%s", '\'' . addslashes($value) . '\'');
                } else {
                    $re_tmp .= sprintf(",%s", addslashes($value));
                }
            }
            $result .= substr($re_tmp, 1) . ')';
        }
        return substr($result, 1);
    }

    /**
     * @desc 拼装where条件字串
     * @param 1维数组或2维数组
     * @return String
     */
    private function whereKV($where) {
        $_res = '';
        $_operate = self::operate($where); 
        
        foreach ($where as $key => $value) {            
            if (!is_array($where[$key]) && $key === '_logic_')
                continue;

            if (!is_array($where[$key]) && !is_numeric($key)) {
                $_res .= $_operate . trim($this->updateKV(array($key => $value)));
                continue;
            } else if (is_numeric($key) && is_string($value)) {
                $_res .= sprintf("%s%s", $_operate, strtolower($value));
                continue;                
            }

            if (!count($value))
                continue;
            
            $_res .= $this->toFields($value, $_operate);
        }
        return mb_substr(trim($_res), mb_strlen($_operate, 'utf8') - 2, 'UTF-8');
    }

    /**
     * @desc 拆分运算规则
     * @param 条件数组
     * @return String
     */
    private static function operate(&$where) {
        // 默认进行 AND 运算
        $_str = ' AND ';
        if (array_key_exists('_logic_', $where)) {
            // 逻辑运算规则 例如 OR AND
            $_str = ' ' . strtoupper($where['_logic_']) . ' ';
            unset($where['_logic_']);
        }
        return $_str;
    }

    /**
     * @desc 设定调试模式值
     */
    public function setDebug($values = true) {
        self::$debug = $values;
        return $this;
    }

    /**
     * @desc 关闭数据连接
     */
    private static function ClearParse() {
        self::$instance = null;
    }
    
    /**
     * 
     */
    private function toFields(array $values, $oper=null) {
        static $old_oper = '';
        $fields = ''; $_exps = 'eq';
        if (empty($values)) return ;
        if (empty($oper) || isset($values['_logic_'])) {
            $old_oper = $oper;
            $oper = self::operate($values);
        }
        if (isset($values['_exp_'])) {
            $_exps = strtolower($values['_exp_']);
            unset($values['_exp_']);
        }
        foreach ($values as $k => $v) {
            if (is_array($v)) {
                $f_arr = current($v);
                $tmp_v = is_array($f_arr) ? $v : array($v);
                                
                // array(array('fid'=>'(1,2,3,4,5)','_exp_'=>'in'), array('aid'=>'(1,2,3,4,5)','_exp_'=>'in'), '_logic_'=>'or')
                $fields .= $oper . '(' . str_replace($oper, '', implode('', array_map(array(&$this, __FUNCTION__), $tmp_v, array($oper)))) . ')';
                $fields = str_replace(')'.$oper.'(', $oper, $fields);
                if ($old_oper && $old_oper != $oper) $fields = str_replace($oper . '(', $old_oper . '(', $fields);
            } else if (is_int($k)) {
                $fields .= sprintf("%s%s", $oper, $this->fmtKey(strtolower($k)));
            } else {
                if ($k != '_exp_') {
                    if (!is_numeric($v)) {
                        if (!in_array($_exps, array('in', 'notin', 'like', 'notlike'))) {
                            $v = '\'' . addslashes($v) . '\'';
                        }
                        $fields .= sprintf("%s%s%s%s", $oper, $this->fmtKey(strtolower($k)), self::$exp[$_exps], $v);
                    } else {
                        $fields .= sprintf("%s%s%s%s", $oper, $this->fmtKey(strtolower($k)), self::$exp[$_exps], addslashes($v));
                    }
                } else if (is_numeric($k) && is_string($v)) {
                    $fields .= sprintf("%s%s", $oper, addslashes($v));
                }
            }
        }
        
        return $fields;
    }
}
?>