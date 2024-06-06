<?php
class Gapper_Permission_Group_Model extends Gapper_Base_Model{
    //权限类型,键值对应原数据库中的module_id
    private $_type=[
       // 0=>'',
        1=>'最高权限',
        2=>'权限管理',
        3=>'成员管理',
        4=>'仪器管理',
        5=>'课题组管理',
        6=>'文件管理',
        7=>'财务管理',
        8=>'仪器收费',
        9=>'仪器预约',
        10=>'送样预约',
        11=>'仪器监控',
        12=>'黑名单',
        13=>'成果管理',
        14=>'仪器统计',
        15=>'GIS监控',
        16=>'视频监控',
        17=>'仪器监控关联',
        18=>'门禁管理',
        19=>'仪器门禁关联',
        20=>'环境监控',
        21=>'CERS',
        22=>'存货管理'
    ];

    public function __construct($gapper_permission=null)
    {
        if (!O||!$gapper_permission instanceof Gapper_Permission_Model) {
            return;
        }
        $this->add_permission($gapper_permission);
    }
    public function add_permission($gapper_permission){
        if (!$gapper_permission||!$gapper_permission instanceof Gapper_Permission_Model) {
            return;
        }
        $this->_data[$this->_type[$gapper_permission->module_id]][]=[
            'name'=>$gapper_permission->name,
            'key'=>$gapper_permission->key
        ];
    
    }
    
    //permission必须有key字段，
    public function get_array(){
        return $this->_data;
    }
}