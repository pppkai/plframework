<?php

/**
  +------------------------------------------------------------------------------
 * PLFrame框架 基类文件
  +------------------------------------------------------------------------------
 * @category lib
 * @package  PLFrame
 * @author   pengzl<pengzl_gz@163.com>
 * @version  $Id: Base.class.php 16 2011-02-21 10:58:23Z pengzl_gz@163.com $
  +------------------------------------------------------------------------------
 */
abstract class Base {

    function __set($name, $value) {
        if (property_exists($this, $name))
            $this->$name = $value;
    }

    function __get($name) {
        //if (isset($this->$name)) return $this->$name;
        if (property_exists($this, $name))
            return $this->$name;
        return;
    }

}

?>