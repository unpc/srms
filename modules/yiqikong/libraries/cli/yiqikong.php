<?php

use \Pheanstalk\Pheanstalk;

class CLI_YiQiKong
{

    const ROUTINGKEY_DIRECTORY = 'directory';
    const ROUTINGKEY_CONTROL = 'control';
    const ROUTINGKEY_RESERV = 'reserv';
    const ROUTINGKEY_RECORD = 'record';
    const ROUTINGKEY_SAMPLE_SETTING = 'sample-setting';
    const ROUTINGKEY_RESERV_SETTING = 'reserv-setting';
    const ROUTINGKEY_CHARGE_SETTING = 'charge-setting';

    static function update_equipment($id)
    {

        $equipment = O('equipment', $id);
        if (!$equipment->id) return;

        $gatewayConfig = YiQiKong::getYiqikongConfig(SITE_ID, LAB_ID);
        $mq = new Pheanstalk($gatewayConfig['mq']['host'], $gatewayConfig['mq']['port']);

        $data = [];

        $data['path'] = 'equipment';
        $data['method'] = 'post';
        $data['rpc_token'] = $gatewayConfig['mq']['x-beanstalk-token'];
        $data['header'] = [
            'x-yiqikong-notify' => TRUE,
        ];

        $data['body']['source_name'] = LAB_ID;
        $data['body']['source_id'] = $equipment->id;
        $data['body']['name'] = $equipment->name;
        $data['body']['en_name'] = $equipment->en_name;
        $data['body']['name_abbr'] = $equipment->name_abbr;

        $data['body']['base_url'] = Config::get('system.base_url');
        $data['body']['socket_url'] = Config::get('yiqikong.socket.url', Config::get('system.base_url'));
        $data['body']['socket_path'] = Config::get('yiqikong.socket.path', '/socket.io');

        $icon_file = Core::file_exists(PRIVATE_BASE . 'icons/equipment/128/' . $equipment->id . '.png', '*');
        if ($icon_file) $icon_url = Config::get('system.base_url') . 'icon/equipment.' . $equipment->id . '.128';
        $data['body']['icon'] = $icon_url ?: '';
        $data['body']['iconMd5'] = $icon_file ? md5_file($icon_file) : '';

        $tag = $equipment->group;
        $group = $tag->id ? [$tag->name] : null;
        $group_n = $tag->id ? [$tag->id => $tag->name] : null;
        while ($tag->parent->id && $tag->parent->root->id) {
            array_unshift($group, $tag->parent->name);
            $group_n[$tag->parent->id] = $tag->parent->name;
            $tag = $tag->parent;
        }
        $data['body']['institute'] = Event::trigger('yiqikong.root.group', $group) ?: $group;
        //这里是为了兼容历史app，增加字段
        $data['body']['institute_n'] = $group_n;

        $data['body']['ref_no'] = $equipment->ref_no;

        $data['body']['model'] = $equipment->model_no;
        $data['body']['spec'] = $equipment->specification;
        $data['body']['price'] = $equipment->price;

        $data['body']['manu_name'] = $equipment->manufacturer;
        $data['body']['manu_place'] = $equipment->manu_at;
        $data['body']['manu_date'] = $equipment->manu_date;

        $data['body']['purchased_date'] = $equipment->purchased_date;
        $data['body']['enroll_date'] = $equipment->atime;

        $data['body']['tech_specs'] = $equipment->tech_specs;
        $data['body']['features'] = $equipment->features;
        $data['body']['accessories'] = $equipment->configs;
        $data['body']['application'] = $equipment->domain;

        $data['body']['contact_name'] = join(', ', Q("{$equipment} user.contact")->to_assoc('id', 'name'));
        $data['body']['contact_phone'] = $equipment->phone;
        $data['body']['contact_email'] = $equipment->email;

        $incharges = $incharges_n = [];
        foreach (Q("{$equipment} user.incharge") as $incharge) {
            $incharges[$incharge->yiqikong_id ?: $incharge->name] = $incharge->name;
            $incharges_n[$incharge->id] = ['name' => $incharge->name, 'yiqikong_id' => $incharge->yiqikong_id];
        }

        $data['body']['incharges'] = $incharges;
        $data['body']['incharges_n'] = $incharges_n;

        $data['body']['can_reserv'] = $equipment->accept_reserv ? 1 : 0;
        $data['body']['can_sample'] = $equipment->accept_sample ? 1 : 0;

        $data['body']['sample_lock'] = $equipment->sample_lock == 'on' ? 1 : 0;
        $data['body']['reserv_lock'] = $equipment->reserv_lock == 'on' ? 1 : 0;

        if (Module::is_installed('yiqikong_approval')) {
            $data['body']['need_approval'] = $equipment->need_approval ? 1 : 0;
        }

        $data['body']['auto_apply'] = $equipment->sample_autoapply ? 1 : 0;

        $data['body']['alias_name'] = $equipment->Alias;
        $data['body']['en_name'] = $equipment->ENGName;

        $data['body']['note'] = $equipment->OtherInfo;
        $data['body']['weight'] = Config::get('equipment.yiqikong.weight', 0);

        $root = Tag_Model::root('location');
        $data['body']['location'] = join(' ',Q("{$equipment} tag_location[root=$root]")->to_assoc('id','name'));
        $data['body']['longitude'] = Config::get('gis.longitude', 0);
        $data['body']['latitude'] = Config::get('gis.latitude', 0);
        $data['body']['status'] = $equipment->status;

        if (Module::is_installed('gismon')) {
            $gis_device = O('gis_device', ['object' => $equipment]);
            $building = $gis_device->building;
            if ($building->id) {
                $data['body']['longitude'] = $building->longitude;
                $data['body']['latitude'] = $building->latitude;
            }
        }

        if (!$equipment->yiqikong_id) {
            $str = $data['body']['source_name'] . ':' . $data['body']['source_id'];
            $equipment->yiqikong_id = hash_hmac('sha1', $str, self::ROUTINGKEY_DIRECTORY);
            $equipment->save();
        }

        $root = Tag_Model::root('equipment');
        $tags = Q("{$equipment} tag_equipment[root=$root]")->to_assoc('id', 'name');

        $data['body']['tags'] = join(', ', $tags);
        $data['body']['tags_n'] = $tags;
        $data['body']['uuid'] = $equipment->yiqikong_id;
        $data['body']['device_id'] = $equipment->control_address;
        $data['body']['share'] = (int)$equipment->yiqikong_share;
        $data['body']['bluetooth_serial_address'] = $equipment->bluetooth_serial_address;
        $data['body']['control_mode'] = $equipment->control_mode;
//        $data['body']['is_preheat'] = $equipment->is_preheat ?? 0;
//        $data['body']['is_cooling'] = $equipment->is_cooling ?? 0;
        $preheatCooling = Q("eq_preheat_cooling[equipment={$equipment}]:sort('ctime D')")->current();
        $data['body']['preheat'] = $preheatCooling->id ? $preheatCooling->preheat_time : 0;
        $data['body']['cooling'] = $preheatCooling->id ? $preheatCooling->cooling_time : 0;

        $root = Tag_Model::root('location');
        $tags = Q("{$equipment} tag_location[root=$root]")->to_assoc('id', 'name');
        $data['body']['location'] = join(', ', $tags);
        $data['body']['location_n'] = $tags;

        //获取当前收费信息,应该app传用户ID获取
        $chargeSetting = [];

        $department = $equipment->billing_dept;

        if ($department->id) {
            $u = O('user');
            $standards = EQ_Charge::charge_template_standards($equipment, null, $u);
            if ($equipment->accept_reserv && $standards['reserv']) {
                $rc = $equipment->charge_script['reserv'] && !$standards['record'] ? '预约 / 使用计费设置' : '预约计费设置';
                $reservDefault = ['name' => $rc, 'content' => str_replace(['<p>', '</p>', '<span>', '</span>'], ['', '.', '', ''], $standards['reserv'])];
                $reservAdmin = ['name' => $rc, 'content' => '免费使用'];
            }
            if ($standards['record']) {
                $recordDefault = ['name' => '使用计费设置', 'content' => str_replace(['<p>', '</p>', '<span>', '</span>'], ['', '.', '', ''], $standards['record'])];
                $recordAdmin = ['name' => '使用计费设置', 'content' => '免费使用'];
            }
            if ($equipment->accept_sample) {
                $sampleDefault = ['name' => '送样计费设置', 'content' => str_replace(['<p>', '</p>', '<span>', '</span>'], ['', '.', '', ''], $standards['sample'])];
                $sampleAdmin = ['name' => '送样计费设置', 'content' => '免费使用'];
            }
        }

        $set = [];
        $setDefault = [];
        isset($reservAdmin) ? $set[] = $reservAdmin : '';
        isset($recordAdmin) ? $set[] = $recordAdmin : '';
        isset($sampleAdmin) ? $set[] = $sampleAdmin : '';

        isset($reservDefault) ? $setDefault[] = $reservDefault : '';
        isset($recordDefault) ? $setDefault[] = $recordDefault : '';
        isset($sampleDefault) ? $setDefault[] = $sampleDefault : '';

        $chargeSetting['admin'] = $chargeSetting['incharge'] = $chargeSetting['genee'] = $set;

        $chargeSetting['default'] = $setDefault;

        $data['body']['charge_template'] = $department->id ? $chargeSetting : [];;

        $mq->useTube('stark')->put(json_encode($data, TRUE));

    }

    static function update_equipments()
    {
        //应该同步所有状态的仪器过去
        $eqs = Q("equipment");
        $start = $num = 0;
        $step = 10;
        $total = $eqs->total_count();

        while ($start <= $total) {

            $equipments = $eqs->limit($start, $step);

            foreach ($equipments as $equipment) {
                self::update_equipment($equipment->id);
                if ($num % 500 == 0) {
                    sleep(1);
                }
                $num++;
                echo "Push Equipment[" . $equipment->id . "]\n";
            }
            $start += $step;
        }
        Upgrader::echo_success("Done.");
    }

    static function update_equipment_settings()
    {
        $status = EQ_Status_Model::IN_SERVICE;
        $eqs = Q("equipment[status={$status}]");
        $start = 0;
        $step = 10;
        $total = $eqs->total_count();

        while ($start <= $total) {

            $equipments = $eqs->limit($start, $step);

            foreach ($equipments as $equipment) {
                self::update_equipment_setting($equipment->id);
            }
            $start += $step;
        }
        Upgrader::echo_success("Done.");
    }

    static function update_equipment_setting($id = 0)
    {
        $equipment = O('equipment', $id);
        if (!$equipment->id || !$equipment->yiqikong_id) return;

        $gatewayConfig = YiQiKong::getYiqikongConfig(SITE_ID, LAB_ID);
        $mq = new Pheanstalk($gatewayConfig['mq']['host'], $gatewayConfig['mq']['port']);

        /* 更新sample的setting设置 */
        $extra = Q("extra[object={$equipment}][type=eq_sample]")->current();
        $extra_fields = new ArrayIterator([]);
        Event::trigger('equipment.reserv.extra.fields', $extra_fields, $equipment);
        if ($extra->id && $equipment->accept_sample > 0) {
            $extra_fields = array_merge(Yiqikong_Extra::format($extra), (array)$extra_fields);
        }
        if ($extra->id && $equipment->accept_sample > 0) {
            $data = [];
            $data['path'] = 'sample/setting';
            $data['method'] = 'post';
            $data['rpc_token'] = $gatewayConfig['mq']['x-beanstalk-token'];
            $data['header'] = [
                'x-yiqikong-notify' => TRUE,
            ];
            $data['body'] = [
                'uuid' => $equipment->yiqikong_id,
                'source_name' => LAB_ID,
                'source_id' => $equipment->id,
                'name' => $equipment->name,
                /* 是否自动批准送样 */
                'auto_apply' => $equipment->sample_autoapply,
                /* 自定义表单 */
                'extrafields' => $extra_fields
            ];

            $mq->useTube('stark')->put(json_encode($data, TRUE));
        }

        /* 更新reserv的setting设置 */
        $extra = Q("extra[object={$equipment}][type=eq_reserv]")->current();
        $extra_fields = new ArrayIterator([]);
        Event::trigger('equipment.reserv.extra.fields', $extra_fields, $equipment);
        if ($extra->id && $equipment->accept_reserv > 0) {
            $extra_fields = array_merge(Yiqikong_Extra::format($extra), (array)$extra_fields);
        }
        if (count($extra_fields) && $equipment->accept_reserv) {
            $data = [];
            $data['path'] = 'reserve/setting';
            $data['method'] = 'post';
            $data['rpc_token'] = $gatewayConfig['mq']['x-beanstalk-token'];
            $data['header'] = [
                'x-yiqikong-notify' => TRUE,
            ];
            $data['body'] = [
                'uuid' => $equipment->yiqikong_id,
                'source_name' => LAB_ID,
                'source_id' => $equipment->id,
                'name' => $equipment->name,
                /* 块状预约 */
                'accept_block_time' => $equipment->accept_block_time,
                'reserv_interval_time' => $equipment->reserv_interval_time,
                'reserv_align_time' => $equipment->reserv_align_time,
                'reserv_block_data' => (array)$equipment->reserv_block_data,
                /* 自定义表单 */
                'extrafields' => $extra_fields
            ];
            $mq->useTube('stark')->put(json_encode($data, TRUE));
        }

        if ($equipment->charge_setting) {
            $lab = YiQiKong_Lab::default_lab();
            $root = $equipment->get_root();
            $tag = Q("{$lab} tag[root={$root}]:sort(weight A)")->current()->name;
            $data = [];
            $data['path'] = 'charge/setting';
            $data['method'] = 'post';
            $data['header'] = [
                'x-yiqikong-notify' => TRUE,
            ];
            $data['rpc_token'] = $gatewayConfig['mq']['x-beanstalk-token'];
            $data['body'] = [
                'uuid' => $equipment->yiqikong_id,
                'source_name' => LAB_ID,
                'source_id' => $equipment->id,
                'name' => $equipment->name,
                'record_type' => $equipment->charge_template['record'] ?: $equipment->charge_template['reserv'],
                'record_unit' => $equipment->charge_setting['record'][$tag]['unit_price'] ?: ($equipment->charge_setting['reserv'][$tag]['unit_price'] ?: ($equipment->charge_setting['record']['*']['unit_price'] ?: $equipment->charge_setting['reserv']['*']['unit_price'])),
                'record_minimum' => $equipment->charge_setting['record'][$tag]['minimum_fee'] ?: ($equipment->charge_setting['reserv'][$tag]['minimum_fee'] ?: ($equipment->charge_setting['record']['*']['minimum_fee'] ?: $equipment->charge_setting['reserv']['*']['unit_price'])),
                'sample_type' => $equipment->charge_template['sample'],
                'sample_unit' => $equipment->charge_setting['sample'][$tag]['unit_price'] ?: $equipment->charge_setting['sample']['*']['unit_price'],
                'sample_minimum' => $equipment->charge_setting['sample'][$tag]['minimum_fee'] ?: $equipment->charge_setting['sample']['*']['minimum_fee'],
            ];
            $mq->useTube('stark')->put(json_encode($data, TRUE));
        }

        /* 更新record的setting设置 */
        $extra = Q("extra[object={$equipment}][type=use]")->current();

        if ($extra->id) {
            $data = [];
            $data['path'] = 'record/setting';
            $data['method'] = 'post';
            $data['rpc_token'] = $gatewayConfig['mq']['x-beanstalk-token'];
            $data['header'] = [
                'x-yiqikong-notify' => TRUE,
            ];
            $data['body'] = [
                'uuid' => $equipment->yiqikong_id,
                'source_name' => LAB_ID,
                'source_id' => $equipment->id,
                'name' => $equipment->name,
                /* 自定义表单 */
                'extrafields' => Yiqikong_Extra::format($extra)
            ];

            $mq->useTube('stark')->put(json_encode($data, TRUE));
        }

    }

    static function update_equipment_status()
    {
        if (Config::get('lab.modules')['app']) {
            $status = Q('equipment[yiqikong_id] eq_status');

            $gatewayConfig = YiQiKong::getYiqikongConfig(SITE_ID, LAB_ID);
            $mq = new Pheanstalk($gatewayConfig['mq']['host'], $gatewayConfig['mq']['port']);

            if ($status->total_count()) {

                foreach ($status as $s) {

                    $payload = [
                        'method' => 'post',
                        'path' => 'equipment/status',
                        'rpc_token' => $gatewayConfig['mq']['x-beanstalk-token'],
                        'header' => [
                            'x-yiqikong-notify' => TRUE,
                        ],
                        'body' => [
                            'equipment' => $s->equipment->yiqikong_id,
                            'source_name' => LAB_ID,
                            'source_id' => $s->id,
                            'dtstart' => $s->dtstart,
                            'dtend' => $s->dtend,
                            'status' => $s->status,
                            'description' => $s->description,
                            'ctime' => $s->ctime,
                        ]
                    ];
                    $mq
                        ->useTube('stark')
                        ->put(json_encode($payload, TRUE));

                    echo "仪器状态 {$s->id} 推送成功!\n";
                }
            }

        }
        Upgrader::echo_success("Done.");
    }

    static function update_equipment_trainings()
    {
        if (Config::get('lab.modules')['app']) {
            $train = Q('ue_training[ctime>0]');

            if ($train->total_count()) {
                $gatewayConfig = YiQiKong::getYiqikongConfig(SITE_ID, LAB_ID);
                $mq = new Pheanstalk($gatewayConfig['mq']['host'], $gatewayConfig['mq']['port']);

                foreach ($train as $val) {
                    $payload = [
                        'method' => 'post',
                        'path' => 'equipment/training',
                        'rpc_token' => $gatewayConfig['mq']['x-beanstalk-token'],
                        'header' => [
                            'x-yiqikong-notify' => TRUE,
                        ],
                        'body' => [
                            'equipment' => $val->equipment->yiqikong_id,
                            'user' => $val->user->yiqikong_id,
                            'user_name' => $val->user->name,
                            'user_local' => $val->user->id,
                            'status' => $val->status,
                            'source_name' => LAB_ID,
                            'source_id' => $val->id,
                            'atime' => $val->atime,
                        ]
                    ];
                    $mq
                        ->useTube('stark')
                        ->put(json_encode($payload, TRUE));

                    echo "仪器培训 {$val->id} 推送成功!\n";
                }
            }
        }
        Upgrader::echo_success("Done.");
    }

    //公告历史数据处理
    public static function update_equipment_announces()
    {
        if (!Config::get('lab.modules')['app']) return TRUE;
        $gatewayConfig = YiQiKong::getYiqikongConfig(SITE_ID, LAB_ID);
        $mq = new Pheanstalk($gatewayConfig['mq']['host'], $gatewayConfig['mq']['port']);

        $announces = Q('eq_announce');
        if ($announces->total_count()) {
            foreach ($announces as $announce) {
                if (!$announce->equipment->yiqikong_id) {
                    continue;
                }
                $payload = [
                    'method' => 'post',
                    'path' => 'equipment/announce',
                    'rpc_token' => $gatewayConfig['mq']['x-beanstalk-token'],
                    'header' => [
                        'x-yiqikong-notify' => TRUE,
                    ],
                    'body' => [
                        'equipment' => $announce->equipment->yiqikong_id,
                        'title' => $announce->title,
                        'content' => $announce->content,
                        'author_local' => $announce->author->id,
                        'author' => $announce->author->yiqikong_id,
                        'is_sticky' => $announce->is_sticky,
                        'ctime' => $announce->ctime,
                        'mtime' => $announce->mtime,
                        'source_name' => LAB_ID,
                        'source_id' => $announce->id,
                    ]
                ];
                $mq
                    ->useTube('stark')
                    ->put(json_encode($payload, TRUE));
                //推送关系
                $connects = Database::factory()->query("select * from _r_user_eq_announce where id2 = {$announce->id}")->rows();
                if (!$connects) {
                    continue;
                }
                foreach ($connects as $connect) {
                    Event::trigger('user.eq_announce.connect', O('user', $connect->id1), $announce);
                }
            }
        }
        Upgrader::echo_success("Done.");
    }

    //处理历史关注列表
    public static function update_user_follow()
    {
        $follows = Q("follow");
        $gatewayConfig = YiQiKong::getYiqikongConfig(SITE_ID, LAB_ID);
        $mq = new \Pheanstalk\Pheanstalk($gatewayConfig['mq']['host'], $gatewayConfig['mq']['port']);
        foreach ($follows as $follow){
            $data = [];
            $data['path'] = 'user/follow';
            $data['method'] = "post";
            $data['rpc_token'] = $gatewayConfig['mq']['x-beanstalk-token'];
            $data['header'] = ['x-yiqikong-notify' => true];
            $data['body'] = [
                'source_name' => $follow->object_name,
                'source_id' => $follow->object_id,
                'uuid' => $follow->object->yiqikong_id,
                'user_local' => $follow->user->id,
                'user' => $follow->user->yiqikong_id ?? 0,
            ];
            $mq->useTube('stark')->put(json_encode($data, TRUE));
        }
        Upgrader::echo_success("Done.");
    }

    //处理历史门禁同步
    public static function sync_door(){
        $doors = Q('door');
        foreach ($doors as $door){
            $door->name = $door->name;
            $door->save();
        }
    }

    //处理历史摄像头同步
    public static function sync_vidcam(){

        $vidcams = Q('vidcam');
        foreach ($vidcams as $vidcam){
            $vidcam->name = $vidcam->name;
            $vidcam->save();
        }

        $connects = Database::factory()->query("SELECT * FROM _r_vidcam_equipment")->rows();
        foreach ($connects as $connect){
            $vid = O('vidcam',$connect->id1);
            $eq = O('equipment',$connect->id2);
            if ($vid->id && $eq->id)
                Control_Equipment::on_vidcam_equipment_connect(null,$eq,$vid,$connect->type);
        }

    }
}
