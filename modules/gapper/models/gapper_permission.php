<?php
class Gapper_Permission_Model extends Gapper_Base_Model{
   
    protected $_data=[
        'name'=>'',
        'key'=>'',
        'module_id'=>''
    ];

    //permission必须有key字段，不知道干嘛的先重写一下get_array;
    public function get_array(){
        return $this->_data;
    }
}