<?php

/**
  +------------------------------------------------------------------------------
 * 数据操作中间层类
  +------------------------------------------------------------------------------
 * @category Vendor
 * @package  Db_Separate
 * @author   pengzl<pengzl_gz@163.com>
 * @version  $Id: db_old.class.php 17 2016-10-13 09:33:56Z pengzl $
  +------------------------------------------------------------------------------
 */
final class db {

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
    private static $debug = 1;
    //配置参数
    private static $configs = null;
    //表达式
    protected static $exp = array('eq' => '=', 'neq' => '!=', 'gt' => '>', 'egt' => '>=', 'lt' => '<', 'elt' => '<=', 'in' => ' IN ', 'notin' => ' NOT IN ', 'like' => ' LIKE ');

    public function __construct($conf = null) {
        if (empty(self::$configs)) {
            $_configs_url = is_string($conf) && $conf ? $conf : dirname(__FILE__) . '/db.config.php';
            if (is_array($conf)) {
                self::$configs = $conf;
                $_configs_url = '';
            }
            if (is_file($_configs_url))
                self::$configs = require_once($_configs_url);
            if (empty(self::$configs))
                self::halt("配置文件不存在或路径出错");
        }

        if (!is_object(self::$DB))
            self::connect();
        empty(self::$configs['charset']) ? self::$configs['charset'] = 'utf8' : '';
        if (!self::$DB->set_charset(self::$configs['charset']))
            self::halt("无法将数据库查询的字符集设置为" . self::$configs['charset'] . ",<br />原因:" . self::$DB->error);
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
        self::CloseDB();
    }

    /**
     * @desc 禁止克隆
     */
    private function __clone() {
        
    }

    /**
     * @desc 连接数据库
     */
    private function connect() {
        if (!isset(self::$DB) || !is_object(self::$DB)) {
            $mysqli = new mysqli(
                self::$configs['dbhost'], self::$configs['dbuser'], self::$configs['dbpass'], self::$configs['dbname'], self::$configs['dbport']
            );

            if ($mysqli->connect_errno || $mysqli->connect_errno === NULL)
                self::halt('数据库连接错误:' . $mysqli->connect_error);

            self::$DB = &$mysqli;
        }

        return self::$DB;
    }

    /**
     * @desc execute query
     */
    public function _query($sql) {
        if (false === $result = self::$DB->query($sql))
            self::halt('数据库查询错误:' . self::$DB->error . ' , SQL: ' . $sql);

        self::getErrInfo();
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
        self::$stmt = self::_query("SHOW TABLE STATUS FROM " . self::$configs['dbname']);
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
        self::$stmt = self::_query($sql);

        if (self::$stmt === false || !($row = self::$stmt->$type())) {
            return array();
        }

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
    public function insert($table, $avgs) {
        return self::runUpdateSQL('insert', $table, $avgs);
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

        if ($mode == "insert")
            return self::last_id();
        return $rows_aff;
    }

    /**
     * 执行 insert update delete SQL 语句
     * @param sql
     */
    public function execute($sql) {
        if ($sql == false || empty($sql)) {
            self::halt("SQL is Empty!");
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
     * @desc 返回最后查询中自动产生的id
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
     * @param mode  模式 (SELECT | INSERT | UPDATE | DELETE)
     * @param table 表
     * @param avgs  参数
     *              keys:Array|String (for:SELECT),
     *              values:Array (for:INSERT, UPDATE),
     *              join:Array{[table]{on1,on2}} (for:SELECT),
     *              groupby:Array|String (for:SELECT),
     *              having:Srting (for:SELECT),
     *              where:String/Array,
     *              order:String (for:SELECT),
     *              limit:String
     * @return sql:String
     */
    public function formatSQL($mode, $table, $avgs) {
        if (!is_array($avgs) && empty($avgs)) {
            return false;
        }

        $mode = strtoupper(trim($mode));
        $sql = $mode;

        switch ($mode) {
            case 'SELECT':
                // keys
                if (isset($avgs['keys']) && !in_array($avgs['keys'], array('*', 'SQL_CALC_FOUND_ROWS *'))) {
                    if (!is_array($avgs['keys'])) {
                        $this->mkArray($avgs['keys']);
                    }

                    $sql .= ' ' . $this->selectK($avgs['keys']);
                } elseif (isset($avgs['keys']) && in_array($avgs['keys'], array('SQL_CALC_FOUND_ROWS *'))) {
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

                    if (!empty($where)) {
                        $sql .= ' WHERE ' . $where;
                    }
                }

                // group by
                if (isset($avgs['group'])) {
                    $avgs['group'] = is_array($avgs['group']) ? implode(',', $avgs['group']) : $avgs['group'];
                    $sql .= ' GROUP BY ' . $avgs['group'];

                    // having
                    if (isset($avgs['having'])) {
                        $sql .= ' HAVING ' . $avgs['having'];
                    }
                }

                // order by
                if (isset($avgs['order'])) {
                    $sql .= ' ORDER BY ' . $avgs['order'];
                }

                // limit
                if (isset($avgs['limit'])) {
                    $sql .= ' LIMIT ' . $avgs['limit'];
                }
                break;

            case 'UPDATE':
                // check
                if (!isset($avgs['where'])) {
                    return false;
                }

            case 'INSERT':
                // check
                if (!isset($avgs['value'])) {
                    return false;
                }

                if (!is_array($avgs['value'])) {
                    return false;
                }

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

            case 'DELETE':
                // check
                if (!isset($avgs['where'])) {
                    return false;
                }

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
                break;
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
            if (!is_numeric($value)) {
                $result .= sprintf(",%s=%s", $this->fmtKey(strtolower($key)), "'" . addslashes($value) . "'");
            } else {
                $result .= sprintf(",%s=%s", $this->fmtKey(strtolower($key)), addslashes($value));
            }
        }
        return substr($result, 1);
    }

    /**
     * @desc 拼装where条件字串
     * @param 1维数组或2维数组
     * @return String
     */
    private function whereKV($where) {
        $_exps = 'eq';
        $_res = '';
        $_operate = self::operate($where);

        foreach ($where as $key => $value) {
            if (!is_array($where[$key]) && $key == '_logic_')
                continue;

            if (!is_array($where[$key])) {
                $_res .= $_operate . trim($this->updateKV(array($key => $value)));
                continue;
            }

            if (!count($value))
                continue;

            foreach ($value as $k => $v) {

                if (isset($value['_exp_'])) {
                    $_exps = strtolower($value['_exp_']);
                }

                if ($k != '_exp_') {
                    if (!is_numeric($v)) {
                        if (!in_array($_exps, array('in', 'notin', 'like', 'notlike'))) {
                            $v = "'" . addslashes($v) . "'";
                        }
                        $_res .= sprintf("%s%s%s%s", $_operate, $this->fmtKey(strtolower($k)), self::$exp[$_exps], $v);
                    } else {
                        $_res .= sprintf("%s%s%s%s", $_operate, $this->fmtKey(strtolower($k)), self::$exp[$_exps], addslashes($v));
                    }
                }
            }
        }
        return substr(trim($_res), strlen($_operate) - 2);
    }

    /**
     * @desc 拆分运算规则
     * @param 条件数组
     * @return String
     */
    private static function operate(&$where) {
        if (array_key_exists('_logic_', $where)) {
            // 逻辑运算规则 例如 OR AND
            $_str = ' ' . strtoupper($where['_logic_']) . ' ';
            unset($where['_logic_']);
        } else {
            // 默认进行 AND 运算
            $_str = ' AND ';
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
    private static function CloseDB() {
        self::$DB = null;
        self::$instance = null;
        self::$configs = null;
    }

    /**
     * @desc 获取数据库出错信息
     */
    private function getErrInfo() {
        $tmp = self::getErrNo();
        if ($tmp != '00000' && !empty($tmp)) {
            self::halt(self::$DB->error);
        }
    }

    /**
     * @desc 获取数据库出错代号
     */
    private static function getErrNo() {
        return self::$DB->errno;
    }

    /**
     * @desc 输出出错提示
     */
    static private function halt($msg = '') {
        if (self::$debug) {
            $message = "<html>\n<head>\n";
            $message .= "<meta content=\"text/html; charset=utf-8\" http-equiv=\"Content-Type\">\n";
            $message .= "<style type=\"text/css\">\n";
            $message .= "* {font:12px Verdana;}\n";
            $message .= "</style>\n";
            $message .= "</head>\n";
            $message .= "<body bgcolor=\"#FFFFFF\" text=\"#000000\" link=\"#006699\" vlink=\"#5493B4\"><br />\n";
            $message .= "<b>Error Info:" . htmlspecialchars($msg) . "</b><br />\n";
            //$message .= "<b>Mysql error description</b>: ".htmlspecialchars($msg)."\n<br />";
            $message .= "<b>Date</b>: " . date("Y-m-d @ H:i") . "\n<br />";
            $message .= "<b>Script file</b>: http://" . $_SERVER['HTTP_HOST'] . getenv("REQUEST_URI") . "\n<br />";
            $message .= "</body>\n</html>";
        } else {
            $message = "<html>\n<head>\n";
            $message .= "<meta content=\"text/html; charset=utf-8\" http-equiv=\"Content-Type\">\n";
            $message .= "<style type=\"text/css\">\n";
            $message .= "* {font:12px Verdana;}\n";
            $message .= "</style>\n";
            $message .= "</head>\n";
            $message .= "<body bgcolor=\"#FFFFFF\" text=\"#000000\" link=\"#006699\" vlink=\"#5493B4\"><br />\n";
            $message .= "<b>SQL Error Info:" . htmlspecialchars("您浏览的页面暂时发生了错误！请稍后再试～") . "</b><br />\n";
            //$message .= "<b>Mysql error description</b>: ".htmlspecialchars($msg)."\n<br />";
            $message .= "<b>Error Date</b>: " . date("Y-m-d @ H:i") . "\n<br />";
            //$message .= "<b>Script file</b>: http://".$_SERVER['HTTP_HOST'].getenv("REQUEST_URI")."\n<br />";
            $message .= "</body>\n</html>";
        }
        echo $message;
        exit(0);
    }

}

?>