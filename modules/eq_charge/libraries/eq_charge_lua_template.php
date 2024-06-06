<?php

class EQ_Charge_LUA_Template extends EQ_Lua {

    private $_equipment;
    private $_user;

    function __construct($equipment, $type) {
        
        $template_standard = $equipment->template_standard;
        $script = $template_standard[$type];
        parent::__construct($script);

        $this->_equipment = $equipment;
        $this->_user = L('ME');

        //通过assign为空值, 解决lua错误报错问题
        $this->lua->assign('start_time', 0);
        $this->lua->assign('end_time', 0);
        $this->lua->assign('user_id', $this->_user->id);
        $this->lua->assign('id', 0);
        $this->lua->assign('currency', Config::get('lab.currency_sign', '¥'));

        //LUA 暴露的函数
        $this->lua->registerCallback('lab_group', [$this, 'lab_group']);
        $this->lua->registerCallback('lab_tag', [$this, 'lab_tag']);
        $this->lua->registerCallback('user_tag', [$this, 'user_tag']);
        $this->lua->registerCallback('charge_tag', [$this, 'charge_tag']);
        $this->lua->registerCallback('charge_tag_value', [$this, 'charge_tag_value']);

        $this->lua->registerCallback('records', [$this, 'records']);
        $this->lua->registerCallback('reservs', [$this, 'reservs']);
        $this->lua->registerCallback('sample', [$this, 'sample']);
        $this->lua->registerCallback('record', [$this, 'record']);
        $this->lua->registerCallback('connect_record', [$this, 'connect_record']);
        $this->lua->registerCallback('connect_samples', [$this, 'connect_samples']);
        $this->lua->registerCallback('user_tags', [$this, 'user_tags']);

        $this->lua->registerCallback('cut', [$this, 'cut']);

        $this->lua->registerCallback('T', [$this, 'T']);
    }

    function lab_group($name) {
        $labs = Q("$this->_user lab");
        $q_name = Q::quote($name);
        $root = Tag_Model::root('group');
        $tags = Q("tag_group[root=$root]")->to_assoc('id','name');
        if($labs->total_count() && in_array($q_name,$tags)) {
            return 1;
        }
        return NULL;
    }

    function lab_tag($name) {
        $user = $this->_user;
        $root = $this->_equipment->get_root();
        return self::user_has_tag($name, $user, $root);
    }

    function user_tag($name) {
        $user = $this->_user;

        $root = [$this->_equipment->get_root(), Tag_Model::root('equipment_user_tags')];

        return self::user_has_tag($name, $user, $root) ? 1 : NULL;
    }

    function charge_tag() {
        return FALSE;
    }

    function charge_tag_value() {
        return 0;
    }

    function run(array $args) {
        return parent::run($args);
    }

    //I18N::T('eq_charge', xx, xx) alias
    function T() {
        $args = func_get_args();
        array_unshift($args, 'eq_charge');
        return call_user_func_array('I18N::T', $args);
    }
    /* 查找到指定时间段内交错的所有使用记录 */
    function records() {
        return [
            1 => [
                'id'=> 0,
                'start_time'=> 0,
                'end_time'=> 0,
                'samples'=> 0,
                'reserv_id'=> 0,
            ],
        ];
    }

    /* 查找送样记录关联的使用记录信息 */
    function connect_record() {
        return [
            'id'=> 0,
            'start_time'=> 0,
            'end_time'=> 0,
            'samples'=> 0,
            'reserv_id'=> 0,
        ];
    }

    /* 查找时间段内的所有的预约记录信息 */
    function reservs() {
        return [
            1=> [
                'id'=> 0,
                'start_time'=> 0,
                'end_time'=> 0,
            ],
        ];
    }

    /* 返回当前传入sample信息 */
    function sample() {
        return [
            'id' => 0,
            'start_time' => 0,
            'end_time' => 0,
            'count' => 0,
            'status' => 0,
            'submit_time' => 0,
            'pickup_time' => 0,
        ];
    }

    /* 返回当前传入使用记录的信息 */
    function record() {
        return [
            'id'=> 0,
            'start_time'=> 0,
            'end_time'=> 0,
            'samples'=> 0,
            'reserv_id'=> 0,
        ];
    }

    /* 返回某使用记录关联的送样记录 */
    function connect_samples() {
        return [
            1=> [
                'id' => 0,
                'start_time' => 0,
                'end_time' => 0,
                'count' => 0,
                'status' => 0,
                'submit_time' => 0,
                'pickup_time' => 0,
            ],
        ];
    }

     /*
       Array
       (
       [1] => vip1
       [2] => vip
       [3] => *
       )
     */

    //返回当前用户或者uid用户的用户标签
    function user_tags($uid=NULL) {
        $user = $uid ? O('user', $uid) : $this->_user;
        $equipment = $this->_equipment;
        $root = $equipment->get_root();
        $tags = Q("tag[root={$root}]:sort(weight A)");

        $tag_array = [];
        $num = 1;
        foreach ($tags as $tag) {
            if(self::user_has_tag($tag->name, $user, $root)){
                $tag_array[$num] = $tag->name;
                $num++;
            }
        }
        
        $root = Tag_Model::root('equipment_user_tags');
        $tags = Q("tag_equipment_user_tags[root={$root}]:sort(weight A)");
        foreach ($tags as $tag) {
            if(self::user_has_tag($tag->name, $user, $root)){
                $tag_array[$num] = $tag->name;
                $num++;
            }
        }
        return count($tag_array) ? $tag_array : NULL;
    }

    function cut() {
        return [
            1 => [
                's'=> 0,
                'e'=> 0,
                'a'=> 0,
                'b'=> 0,
            ]
        ];
    }

    //将配置与代码融合
    static function convert_script() {}

    static function check_syntax() {}
}
