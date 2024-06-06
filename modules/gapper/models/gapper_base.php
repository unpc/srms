<?php
/*
* @Date:2018-11-19 09:24:01
* @Author: LiuHongbo
* @Email: hongbo.liu@geneegroup.com
* @Description:gapper模型的基础类，所有的gappermodel都继承自该类
*/
abstract class Gapper_Base_Model
{
    protected $_data = [];//该数组用来存储model中的成员变量，子类中重写
    public function __construct($O = null)
    {
        if (!$O) {
            return;
        }
        foreach (array_keys($this->_data) as $_key) {
            $value = $O->$_key;
            $this->_data[$_key] = $value ?: '';

        }
    }
    public function __set($key, $value)
    {
         if (array_key_exists($key, $this->_data)) {
            $this->_data[$key] = $value ?: '';
        }
    }
    public function __get($key)
    {
        if (array_key_exists($key, $this->_data));
        {
            return $this->_data[$key];
        }
    }
    public function get_array()
    {
        return $this->_data;
    }
}
