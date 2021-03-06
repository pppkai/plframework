<?php

/**
  +------------------------------------------------------------------------------
 * PLFrame框架 缓存类
  +------------------------------------------------------------------------------
 * @category lib
 * @package  PLFrame
 * @author   pengzl<pengzl_gz@163.com>
 * @version  $Id: Cache.class.php 16 2011-02-21 10:58:23Z pengzl_gz@163.com $
  +------------------------------------------------------------------------------
 */
class Cache extends Base {

    private $m_expire_seconds = 0;
    private $m_ssn = '';
    private $m_desc_key = '';
    private $m_conn = null;
    private static $m_configs = null;

    /**
     * @desc 构造函数
     */
    function __construct($serv = 'ssn_server_01') {
        $_serv = empty($serv) ? 'ssn_server_01' : $serv;
        if (!self::$m_configs)
            self::$m_configs = require_once(PLFRAME_PATH . '/Common/cache.inc.php');

        $conn = new Memcache();
        foreach (self::$m_configs['ssn'] as $ssn) {
            extract(parse_url($ssn), EXTR_OVERWRITE);
            $conn->addServer($host, $port);
            unset($host);
            unset($port);
        }

        $this->m_expire_seconds = self::$m_configs['expire_seconds'];
        $this->m_ssn = isset(self::$m_configs['ssn'][$_serv]) ? self::$m_configs['ssn'][$_serv] : self::$m_configs['ssn']['ssn_server_01'];
        $this->m_desc_key = isset(self::$m_configs['desc'][$_serv]) ? self::$m_configs['desc'][$_serv] : self::$m_configs['desc']['ssn_server_01'];
        $this->m_conn = $conn;
    }

    /**
     * 添加一条缓存数据
     *
     * @param  string $cid       - 缓存cid
     * @param  array  $keyvalues - 缓存数据
     */
    function add($cid, $keyvalues) {
        if (!$this->m_conn->set($cid, $keyvalues, MEMCACHE_COMPRESSED, $this->m_expire_seconds)) {
            throw_exception(L('_CAN_NOT_ADD_CACHE_') . ' ssn:' . $this->m_ssn . ' cid:' . $cid);
        }
    }

    /**
     * 修改缓存生存时间
     *
     * @param  string $seconds       - 缓存失效时间 (单位:秒)
     */
    function set_expire_seconds($seconds) {
        $this->m_expire_seconds = $seconds;
        return $this;
    }

    /**
     * 获取缓存生存时间
     *
     * @return  time - 缓存失效时间 (单位:秒)
     */
    function get_expire_seconds() {
        return $this->m_expire_seconds;
    }

    /**
     * 修改缓存数据
     *
     * @param  string $cid       - 缓存cid
     * @param  array  $keyvalues - 新缓存数据
     */
    function set($cid, array $keyvalues) {
        $data = $this->m_conn->get($cid);
        if ($data === false) {
            try {
                $this->add($cid, $keyvalues);
            } catch (Exception $e) {
                return false;
            }
        } else {
            foreach ($keyvalues as $key => $value) {
                $data[$key] = $value;
            }

            if (!$this->m_conn->set($cid, $data, MEMCACHE_COMPRESSED, $this->m_expire_seconds)) {
                return false;
            }
        }
    }

    /**
     * 获取缓存里的数据
     *
     * @param  string $cid - 缓存sid
     * @param  mixed  $key - 需要获取的缓存数据
     * @return mixed
     */
    function get($cid, $key = null) {
        $data = $this->m_conn->get($cid);
        if ($data === false) {
            $result = false;
        } elseif ($key === null) {
            $result = $data;
        } elseif (is_string($key)) {
            $result = $data[$key];
        } else {
            $result = array();
            foreach ($key as $the_key) {
                $result[$the_key] = $data[$the_key];
            }
        }
        return $result;
    }

    /**
     * 删除缓存里的数据
     *
     * @param  string $cid - 缓存sid
     * @param  mixed  $key - 需要删除的缓存数据
     */
    function del($cid, $key = null) {
        $data = $this->m_conn->get($cid);
        if ($data === false) {
            return;
        }

        if ($key === null) {
            $data = array();
        } elseif (is_string($key)) {
            unset($data[$key]);
        } else {
            foreach ($key as $the_key) {
                unset($data[$key]);
            }
        }
        if (!$this->m_conn->set($cid, $data, MEMCACHE_COMPRESSED, $this->m_expire_seconds)) {
            throw_exception('_CAN_NOT_DEL_CACHE_' . " ssn: {$this->m_ssn} cid: {$cid}");
        }
    }

    /**
     * 删除缓存
     *
     * @param  string $cid - 缓存sid
     */
    function destroy($cid) {
        if (!$this->m_conn->delete($cid)) {
            throw_exception('_CAN_NOT_DEL_CACHE_' . " cid: {$cid}");
        }
    }

    /**
     * 生成缓存key
     *
     * @param  string $hash - 缓存sid
     */
    function makeHashKey($hash) {
        return 'memcached_ssn_' . $this->m_desc_key . '_hash_' . sha1($hash);
    }

    /**
     * 查看memcached服务器状态
     *
     * @param  string $cid - 缓存sid
     */
    function get_status() {
        return $this->m_conn->getExtendedStats();
    }

    /**
     * @ 加入TAG标志(需要安装有memcached-tag才可以使用)
     * @param   string          $tagName   标签名字
     * @param   string | array  $key  标签关联的缓存ID   array('key1', 'key2', ...) OR 'key1'
     */
    function addTag($tagName, $key) {
        $this->m_conn->tag_add($tagName, $key);
    }

    /**
     * @ 删除TAG标志下所有缓存(需要安装有memcached-tag才可以使用)
     * @param   string | array  $tagName  标签关联的缓存ID   array('tagName1', 'tagName2', ...) OR 'tagName'
     */
    function destroyByTag($tagName) {
        $this->m_conn->tag_delete($tagName);
    }

}