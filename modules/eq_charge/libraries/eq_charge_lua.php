<?php

#ifdef (equipment.enable_charge_script)
class EQ_Charge_LUA extends EQ_Lua
{

    private $_charge;
    private $_source;
    private $_dtstart;
    private $_dtend;
    private $_equipment;
    private $_uid;
    private $_id;
    private $_charge_type;
    private $_user;
    private $_status;
    private $_currency;

    function __construct($charge)
    {
        $this->_charge = $charge;
        $source = $this->_source = $charge->source;
        $equipment = $this->_equipment = $source->equipment;
        $this->_id = (int)$source->id;
        $source_name = $source->name();

        switch ($source_name) {
            case 'eq_sample':
                $this->_uid = $source->sender->id;
                $this->_user = $source->sender->id ? $source->sender : L('ME');
                $charge_type = $this->_charge_type = 'sample';
                break;
            case 'eq_reserv':
                $this->_uid = $source->user->id;
                $this->_user = $source->user;
                $charge_type = $this->_charge_type = 'reserv';
                break;
            case 'eq_record':
                $this->_uid = $source->user->id;
                $this->_user = $source->user;
                $charge_type = $this->_charge_type = 'record';
                break;
            case 'sample_element':
                $this->_uid = $source->user->id;
                $this->_user = $source->user;
                $charge_type = $this->_charge_type = 'sample_form';
                break;
            case 'service_apply_record':
                $this->_uid = $source->user->id;
                $this->_user = $source->user;
                $charge_type = $this->_charge_type = 'service';
                break;
            default:
                $this->_uid = $source->user->id;
                $this->_user = $source->user;
                $charge_type = $this->_charge_type = $charge->charge_type;
                break;
        }

        $script = $equipment->charge_script[$charge_type];

        $this->_dtstart = (int)$source->dtstart;
        $this->_dtend = (int)$source->dtend;
        $this->_status = (int)$source->status;
        $this->_currency = Config::get('lab.currency_sign', '¥');

        parent::__construct($script);

        //LUA 暴露的变量
        $this->lua->assign('start_time', $this->_dtstart);
        $this->lua->assign('end_time', $this->_dtend);
        $this->lua->assign('user_id', (int)$this->_uid);
        $this->lua->assign('id', (int)$this->_id);
        $this->lua->assign('status', $this->_status);
        $this->lua->assign('currency', $this->_currency);

        //LUA 暴露的函数
        $this->lua->registerCallback('lab_group', [$this, 'lab_group']);
        $this->lua->registerCallback('lab_tag', [$this, 'lab_tag']);
        $this->lua->registerCallback('user_tag', [$this, 'user_tag']);
        $this->lua->registerCallback('charge_tag', [$this, 'charge_tag']);
        $this->lua->registerCallback('charge_tag_value', [$this, 'charge_tag_value']);
        $this->lua->registerCallback('extra_field_value', [$this, 'extra_field_value']);

        $this->lua->registerCallback('records', [$this, 'records']);
        $this->lua->registerCallback('reservs', [$this, 'reservs']);
        $this->lua->registerCallback('sample', [$this, 'sample']);
        $this->lua->registerCallback('sample_records', [$this, 'sample_records']);
        $this->lua->registerCallback('record', [$this, 'record']);
        $this->lua->registerCallback('sample_form', [$this, 'sample_form']);
        $this->lua->registerCallback('connect_record', [$this, 'connect_record']);
        $this->lua->registerCallback('connect_samples', [$this, 'connect_samples']);
        $this->lua->registerCallback('user_tags', [$this, 'user_tags']);
        $this->lua->registerCallback('material_record', [$this, 'material_record']);

        $this->lua->registerCallback('cut', [$this, 'cut']);
        $this->lua->registerCallback('lab_reserv_time', [$this, 'lab_reserv_time']);
        $this->lua->registerCallback('lab_record_time', [$this, 'lab_record_time']);

        $this->lua->registerCallback('T', [$this, 'T']);
        $this->lua->registerCallback('zoneDiscount_php', [$this, 'zoneDiscount']);
        //这里以后可能会成为通用功能，所以写到通用模块
        $this->lua->registerCallback('user_tag_discount', [$this, 'user_tag_discount']);
        $this->lua->registerCallback('registration_projects', [$this, 'registration_projects']);
        $this->lua->registerCallback('is_last_record', [$this, 'is_last_record']);

        $this->lua->registerCallback('service_apply_record', [$this, 'service_apply_record']);

    }

    function __set($name, $value)
    {
        $this->lua->assign($name, $value);
    }

    function lab_group($name)
    {
        $labs = Q("$this->_user lab");
        $q_name = Q::quote($name);
        $root = Tag_Model::root('group');
        $tags = Q("tag_group[root=$root]")->to_assoc('id', 'name');
        if ($labs->total_count() && in_array($q_name, $tags)) {
            return 1;
        }
        return NULL;
    }

    function lab_tag($name)
    {
        $user = $this->_user;
        $root = $this->_equipment->get_root();
        return self::user_has_tag($name, $user, $root) ? 1 : NULL;
    }

    function user_tag($name)
    {
        $user = $this->_user;

        $root = [$this->_equipment->get_root(), Tag_Model::root('equipment_user_tags')];

        return self::user_has_tag($name, $user, $root) ? 1 : NULL;
    }

    function charge_tag($name)
    {
        $tags = (array)$this->_charge->charge_tags;
        return isset($tags[$name]) ? 1 : NULL;
    }

    function charge_tag_value($name)
    {
        $tags = (array)$this->_charge->charge_tags;
        return $this->charge_tag($name) === NULL ? 0 : (float)$tags[$name];
    }

    //用于获取自定义表单值
    function extra_field_value($category, $name, $key = NULL, $prop = '')
    {
        $type = $this->_source->name();
        if ($prop) {
            $fields = O('extra', ['object' => $this->_source->equipment, 'type' => $prop])->get_fields($category);
            if ($type == 'eq_record') {
                $values = O('extra_value', ['object' => $this->_source->reserv])->values;
            } else if ($type == 'eq_reserv') {
                // 预约保存后开始计费的时候，并没有保存自定义表单，不能从O('extra_value')里面取
                if (isset(L('add_component_form')['extra_fields'])) $values = L('add_component_form')['extra_fields'];
                else $values = O('extra_value', ['object' => $this->_source])->values;
            }
        } else {
            if ($type == 'eq_record') $type = 'use';
            $fields = O('extra', ['object' => $this->_source->equipment, 'type' => $type])->get_fields($category);
            $values = $this->_source->extra_fields;
        }

        if ($values) foreach ($values as $k => $v) {
            if ($fields[$k]['title'] != $name) continue;
            switch ($fields[$k]['type']) {
                case Extra_Model::TYPE_TEXT:
                case Extra_Model::TYPE_TEXTAREA:
                case Extra_Model::TYPE_NUMBER:
                case Extra_Model::TYPE_RADIO:
                case Extra_Model::TYPE_SELECT:
                    $value = $v;
                    break;
                case Extra_Model::TYPE_RANGE:
                case Extra_Model::TYPE_CHECKBOX:
                default:
                    $value = $key !== NULL ? $v[$key] : '';
                    break;
            }
        }

        return $value ?: 0;
    }

    function run(array $args)
    {
        $charge = $this->_charge;
        $equipment = $this->_equipment;
        return parent::run($args);
    }

    //I18N::T('eq_charge', xx, xx) alias
    function T()
    {
        $args = func_get_args();
        array_unshift($args, 'eq_charge');
        return call_user_func_array('I18N::T', $args);
    }

    /* 查找到指定时间段内交错的所有使用记录 */
    function records($dtstart = 0, $dtend = 0)
    {
        if (!$dtstart && !$dtend) {
            $dtstart = $this->_dtstart;
            $dtend = $this->_dtend;
        }

        /*
           获得当前的使用记录，如果修改了某个record，但是还未保存，
           通过records查找的records中有当前修改的这个record；则应该进行替换
         */
        if ($this->_source->name() == 'eq_record') {
            $current_record = $this->_source;
        }

        $equipment = $this->_equipment;

        $records = [];
        $num = 1;
        //搜索有交错的使用结束的使用记录
        foreach (Q("eq_record[equipment={$equipment}][dtend>0][dtstart~dtend={$dtstart}|dtstart~dtend={$dtend}|dtstart={$dtstart}~{$dtend}][dtstart!=$dtend]") as $record) {

            $record = ($current_record->id == $record->id) ? $current_record : $record;

            if (!is_null(L("edit_calculate_{$record}"))) {
                $r = L("edit_calculate_{$record}");
                if ($r->dtend < $dtstart || $r->dtstart > $dtend) {
                    continue;
                } else {
                    $record = $r;
                }
                Cache::L("edit_calculate_{$record}", NULL);
            }

            $records[$num] = [
                'id' => (int)$record->id,
                'user_id' => (int)$record->user->id, //此处直接传递，到lua中会变为string，但是通过(int)后就不会变为string
                'start_time' => (int)$record->dtstart,
                'end_time' => (int)$record->dtend,
                'samples' => (int)$record->samples,
                'reserv_id' => (int)$record->reserv->id ?: 0,
                'lead_time' => (int)$record->preheat,
                'post_time' => (int)$record->cooling
            ];
            $num++;
        }


        return $records;

    }

    /* 查找送样记录关联的使用记录信息 */
    function connect_record($sid = 0)
    {
        $charge = $this->_charge;
        $sample = O('eq_sample', $sid);
        $sample = $sample->id ? $sample : $this->source;

        if ($sample->name() != 'eq_sample') return [];

        $record = $sample->record;
        return [
            'id' => (int)$record->id,
            'start_time' => (int)$record->dtstart,
            'end_time' => (int)$record->dtend,
            'samples' => (int)$record->samples
        ];
    }

    /* 查找时间段内的所有的预约记录信息 */
    function reservs($dtstart = 0, $dtend = 0)
    {
        if (!$dtstart && !$dtend) {
            $dtstart = $this->_dtstart;
            $dtend = $this->_dtend;
        }
        $charge = $this->_charge;
        $equipment = $this->_equipment;

        /*
           获得当前的使用记录，如果修改了某个 reserv ，但是还未保存，
           通过reservs查找的reservs中有当前修改的这个reserv；则应该进行替换
         */
        if ($this->_source->name() == 'eq_reserv') {
            $current_reserv = $this->_source;
        }

        $reservs = [];

        $num = 1;
        foreach (Q("eq_reserv[equipment={$equipment}][dtstart~dtend={$dtstart}|dtstart~dtend={$dtend}|dtstart={$dtstart}~{$dtend}]") as $reserv) {
            $r = ($current_reserv->id == $reserv->id) ? $current_reserv : $reserv;
            $reservs[$num] = [
                'id' => (int)$r->id,
                'start_time' => (int)$r->dtstart,
                'end_time' => (int)$r->dtend,
                'status' => (int)$r->status,
            ];

            $num++;
        }

        return $reservs;

    }

    /* 返回当前传入sample信息 */
    function sample()
    {
        $sample = $this->_source;
        if ($sample->name() != 'eq_sample') return [];
        return [
            'id' => (int)$sample->id,
            'start_time' => (int)$sample->dtstart,
            'end_time' => (int)$sample->dtend,
            'count' => (int)$sample->count,
            'status' => (int)$sample->status,
            'submit_time' => (int)$sample->dtsubmit,
            'pickup_time' => (int)$sample->dtpickup,
            'lead_time' => (int)$sample->preheat,
            'post_time' => (int)$sample->cooling
        ];
    }

    function sample_records($sid)
    {
        $sample = O('eq_sample', $sid);
        $sample = $sample->id ? $sample : $this->source;
        if (!$sample->id) return [];
        if ($sample->name() != 'eq_sample') return [];

        $records = [];
        $num = 1;

        $sample_records = Q("$sample eq_record");

        foreach ($sample_records as $record) {
            $records[$num] = [
                'id' => (int)$record->id,
                'start_time' => (int)$record->dtstart,
                'end_time' => (int)$record->dtend,
                'samples' => (int)$record->samples,
                'reserv_id' => (int)$record->reserv->id ?: 0,
                'reserv_start_time' => (int)$record->reserv->dtstart,
                'reserv_end_time' => (int)$record->reserv->dtend,
                'is_missed' => (int)$record->is_missed,
                'lead_time' => (int)$record->preheat,
                'post_time' => (int)$record->cooling
            ];
            $num++;
        }
        
        return $records;
    }

    /* 返回当前传入使用记录的信息 */
    function record()
    {
        $record = $this->_source;
        if ($record->name() != 'eq_record') return [];

        //【定制】RQ194912 大连理工大学  仪器计费定制化需求。不增加计费方式了
        $record_info = [
            'id' => (int)$record->id,
            'start_time' => (int)$record->dtstart,
            'end_time' => (int)$record->dtend,
            'samples' => (int)$record->samples,
            'reserv_id' => (int)$record->reserv->id ?: 0,
            'reserv_start_time' => (int)$record->reserv->dtstart,
            'reserv_end_time' => (int)$record->reserv->dtend,
            'is_missed' => (int)$record->is_missed,
            'lead_time' => (int)$record->preheat,
            'post_time' => (int)$record->cooling,
            'cancel_minimum_fee' => (int)$record->cancel_minimum_fee,
            'cancel_unit_price' => (int)$record->cancel_unit_price,
            'cancel_lead_time' => (int)$record->cancel_lead_time,
            'cancel_post_time' => (int)$record->cancel_post_time,
        ];
        if ($cc = Event::trigger('eq_charge.get_relative_eq_record_length', $record, $record_info)) {
            if (!empty($cc) && isset($cc['start_time'])) {
                $record_info = array_merge($record_info, $cc);
            }
        }
        return $record_info;
    }

    function sample_form()
    {
        $source = $this->_source;
        if ($source->name() != 'sample_element' || !$source->id) return [];
        $record = O('eq_record', ['sample_element' => $source]);
        if (!$record->id) return [];

        return [
            'id' => (int)$source->id,
            'count' => (int)$source->count,
            'price' => (double)$source->price ?: 0,
            'start_time' => (int)$record->dtstart,
            'end_time' => (int)$record->dtend,
            'samples' => (int)$record->samples,
            'reserv_id' => (int)$record->reserv->id ?: 0,
            'reserv_start_time' => (int)$record->reserv->dtstart,
            'reserv_end_time' => (int)$record->reserv->dtend,
            'is_missed' => (int)$record->is_missed,
            'lead_time' => (int)$record->preheat,
            'post_time' => (int)$record->cooling
        ];
    }

    /* 返回某使用记录关联的送样记录 */
    function connect_samples($rid)
    {
        $charge = $this->_charge;
        $record = O('eq_record', $rid);
        $record = $record->id ? $record : $this->_source;

        if ($record->name() != 'eq_record') return [];

        $samples = [];

        $num = 1;
        foreach (Q("$record eq_sample") as $sample) {

            $samples[$num] = [
                'id' => (int)$sample->id,
                'start_time' => (int)$sample->dtstart,
                'end_time' => (int)$sample->dtend,
                'count' => (int)$sample->count,
                'status' => (int)$sample->status,
                'submit_time' => (int)$sample->dtsubmit,
                'pickup_time' => (int)$sample->dtpickup
            ];

            $num++;
        }

        return $samples;
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
    function user_tags($uid = NULL)
    {
        $user = $uid ? O('user', $uid) : $this->_user;
        $equipment = $this->_equipment;
        $root = $equipment->get_root();
        $tags = Q("tag[root={$root}]:sort(weight A)");

        $tag_array = [];
        $num = 1;
        foreach ($tags as $tag) {
            if (self::user_has_tag($tag->name, $user, $root)) {
                $tag_array[$num] = $tag->name;
                $num++;
            }
        }

        $root = Tag_Model::root('equipment_user_tags');
        $tags = Q("tag_equipment_user_tags[root={$root}]:sort(weight A)");
        foreach ($tags as $tag) {
            if (self::user_has_tag($tag->name, $user, $root)) {
                $tag_array[$num] = $tag->name;
                $num++;
            }
        }
        return count($tag_array) ? $tag_array : NULL;
    }

    function cut($start = 0, $end = 0, $cut_s, $cut_e = 0)
    {
        if (!$cut_s) return [];
        if ($cut_e && $cut_e < $cut_s) return [];

        if (!$start) $start = $this->_source->dtstart;
        if (!$end) $end = $this->_source->dtend;

        $cuts = [];

        $num = 1;

        if ($cut_s <= $start) {
            if (!$cut_e) {
                $cuts[$num] = ['s' => (int)$start, 'e' => (int)$end, 'a' => 1, 'b' => 0];
                $num++;
            } else {
                if ($cut_e <= $start) {
                    $cuts[$num] = ['s' => (int)$start, 'e' => (int)$end, 'a' => 1, 'b' => 0];
                    $num++;
                } else if ($cut_e < $end && $cut_e > $start) {
                    $cuts[$num] = ['s' => (int)$cut_e, 'e' => (int)$end, 'a' => 1, 'b' => 0];
                    $num++;
                }
            }
        } else if ($cut_s >= $end) {
            $cuts[$num] = ['s' => (int)$start, 'e' => (int)$end, 'b' => 1, 'a' => 0];
            $num++;
        } else {
            if (!$cut_e) {
                $cuts[$num] = ['s' => (int)$start, 'e' => (int)$cut_s, 'b' => 1, 'a' => 0];
                $cuts[$num + 1] = ['s' => (int)$cut_s, 'e' => (int)$end, 'a' => 1, 'b' => 0];
            } else {
                if ($cut_e < $end) {
                    $cuts[$num] = ['s' => (int)$start, 'e' => (int)$cut_s, 'b' => 1, 'a' => 0];
                    $cuts[$num + 1] = ['s' => (int)$cut_e, 'e' => (int)$end, 'a' => 1, 'b' => 0];
                } else {
                    $cuts[$num] = ['s' => (int)$start, 'e' => (int)$cut_s, 'b' => 1, 'a' => 0];
                }
            }
        }

        return $cuts;
    }

    //课题组累计预约时长
    function lab_reserv_time($dtstart, $dtend)
    {
        $id = $this->_source->id;
		$user = $this->_user;
		$equipment = $this->_equipment;
		$reservs = Q("$user lab user eq_record[dtstart>=$dtstart][dtstart<$dtend][equipment={$equipment}][reserv][id!={$id}]");
		return (int)$this->get_real_time($reservs) + ($this->_source->dtend - $this->_source->dtstart);
	}
	//课题组累计使用时长
	function lab_record_time($dtstart, $dtend){
        $id = $this->_source->id;
        $user = $this->_user;
        $equipment = $this->_equipment;
        $records = Q("$user lab user eq_record[dtstart>=$dtstart][dtstart<$dtend][equipment={$equipment}][id!={$id}]");
        $time_this = ($this->_source->dtend - $this->_source->dtstart);
        return ((int)$this->get_real_time($records) + $time_this);
    }

    //将配置与代码融合
    static function convert_script($script, $params)
    {
        if (!$script) return FALSE;

        $script = strtr($script, $params);

        return $script;
    }

    static function check_syntax($script, $type, &$error)
    {

        //用于进行检测使用
        $equipment = Faker::equipment();

        switch ($type) {
            case 'reserv' :
                $source = Faker::eq_reserv($equipment);
                break;
            case 'record' :
                $source = Faker::eq_record($equipment);
                break;
            case 'sample' :
                $source = Faker::eq_sample($equipment);
                break;
        }

        $charge = Faker::eq_charge($source);
        $equipment->charge_script = [$type => $script];

        $lua_charge = new EQ_Charge_LUA($charge);

        return $lua_charge->_check_syntax($error);
    }

    private function get_real_time($records)
    {
        $time = 0;
        if (count($records) > 0) {
            foreach ($records as $record) {
                $user = $record->user;
                $equipment = $record->equipment;
                $reserve = $record->reserv;
                if ($record->dtend == 0 || ($reserv->id && $reserv->id == $this->_charge->id)) {
                    continue;
                }
                $time += ($record->dtend - $record->dtstart);
            }
        }
        return $time;
    }

    /**
     * 北大计费脚本引入
     * 传入使用记录之后，根据起始时间，返回指定区间对应的长度*折扣
     * @param $start 开始时间戳
     * @param $end  结束时间戳
     * @param $price [{"min":"00:00:00","max":"2:00:00","discount":80},{"min":"03:00:00","max":"5:00:00","discount":80},{"min":"12:00:00","max":"24:00:00","discount":90}]
     * @param $deep
     * @return float
     */
    public function test($start, $end, $price, $unitPrice)
    {
        static $total;
        //第一次的开始时间戳
        $at = strtotime(date('Y-m-d', $start) . ' ' . $price[0]['end']);//第一次的结束时间戳
        $et = strtotime(date('Y-m-d', $start) . ' ' . $price[1]['end']);//计费结束的时间戳

        if ($end >= $et) {
            $new_start = $et;
            $new_end = $end;
            $end = $et;
        }

        //
        //1,开始时间，结束时间在第一段中
        if ($start < $at && $end <= $at) {
            $hour = ceil(($end - $start) / 3600);//这里可以不去整
            $total += $price[0]['discount'] * $hour;
        }
        //2,开始时间第一段，结束时间第二段
        if ($start < $at && $end > $at && $end <= $et) {
            $one = ceil(($at - $start) / 3600) * $price[0]['price'];
            $two = ceil(($end - $at) / 3600) * $price[1]['price'];
            $total += $one + $two;
        }

        //4,开始时间，结束时间都在第二段
        if ($start >= $at && $end <= $et) {
            $hour = ceil(($end - $start) / 3600);
            $total += $price[1]['price'] * $hour;
        }

        if (isset($new_end) && isset($new_start)) {
            unset($start);
            unset($end);
            unset($at);
            unset($et);
            $this->test($new_start, $new_end, $price, $unitPrice);
        }

        return $total;
    }

    /**
     * 1.拆分当前时间，按着0-24小时拆分成若干个时间段
     * 2.
     */
    public function zoneDiscount($zone)
    {
        $eq = new Zone_EQ_Charge();
        $discount = $eq->zoneDiscount($zone, $this->record());
        if (!is_array($discount)) {
            return [];
        }
        return $discount;
    }

    public function user_tag_discount()
    {
        $equipment = $this->_equipment;
        $root = $equipment->get_root();
        $tags = Q("tag[root={$root}]:sort(weight A)");

        $tag_array = [];
        foreach ($tags as $tag) {
            $tag_array[$tag->name] = $tag->discount ?: 100;
        }
        return count($tag_array) ? $tag_array : NULL;
    }

    /**
     * 取出当前object对应的检测项目，及检测项目针对该用户所有的总价
     */
    public function registration_projects()
    {
        $object = $this->_source;
        $equipment = $this->_equipment;
        $user = $this->_user;
        $newUser = $object->is_new_user;
        $isOutsider = false;

        //判断当前用户是校内外
        if ($newUser) {
            $isOutsider = true;
        } else {
            list($token, $backend) = Auth::parse_token($user->token);
            if ($backend == '') {
                $isOutsider = true;
            } else {
                $lab = Q("{$user} lab")->current();
                $root = Tag_Model::root('group');
                $tag = O('tag_group', ['name' => '校外用户', 'root' => $root]);
                if ($user->group->is_itself_or_ancestor_of($tag) || $lab->group->is_itself_or_ancestor_of($tag)) {
                    $isOutsider = true;
                }
            }
        }

        //获取当前设置的项目
        $projectSettings = Registration::get_projects_setting($equipment);

        $projects = [];
        $total = 0;

        //这里用于未生成sample对象时候，赋予虚属性
        $registration_projects = $object->registration_project_charge ?: $object->get_registration_projects();
        if ($registration_projects) {
            foreach ($registration_projects as $id => $count) {
                foreach ($projectSettings as $setting) {
                    if ($id == $setting['id']) {
                        $per = [];
                        $price = $isOutsider ? $setting['out_price'] : $setting['in_price'];
                        $per['price'] = $price;
                        $per['count'] = $count;
                        $per['total'] = $count * $price;
                        $total += $per['total'];
                        $projects[$setting['name']] = $per;
                    }
                }
            }
        }
        return ['projects' => $projects, 'total' => $total];
    }

    public function __get($key)
    {
        return $this->$key;
    }

    public function is_last_record()
    {
        // $this->error_log($this->_source->id);
        if ($this->_charge_type != 'record') {
            return 1;
        }
        $reserv = $this->_source->reserv;
        $record = Q("eq_record[reserv={$reserv}]:sort(dtstart D):limit(1)")->current();
        return $record->id == $this->_source->id ? 1 : 0;
    }

    function material_record()
    {
        $source = $this->_source;
        $type = [
            'eq_reserv' => '预约记录',
            'eq_sample' => '送样记录',
            'eq_record' => '使用记录',
        ];
        return [
            'materials' => $source->materials,
            'source_id' => $source->id,
            'source_name' => $type[$source->name()],
        ];
    }

    function service_apply_record()
    {
        $source = $this->_source;
        if ($source->name() != 'service_apply_record') return [];
        error_log(print_r([
            'id' => (int)$source->id,
            'samples' => (int)$source->samples,
            'user' => (int)$source->user->id,
            'project_id' => (int)$source->project->id,
            'project_name' => $source->project->name,
        ],true));
        return [
            'id' => (int)$source->id,
            'samples' => (int)$source->samples,
            'user' => (int)$source->user->id,
            'project_id' => (int)$source->project->id,
            'project_name' => $source->project->name,
        ];
    }

}
